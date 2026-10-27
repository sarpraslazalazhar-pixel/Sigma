<?php

namespace Tests\Feature;

use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\BudgetCalculator;
use App\Services\PermohonanWorkflow;
use App\Services\UmpWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class UmpApprovalAndRealizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;

    protected User $kadivUser;

    protected User $pjUser;

    protected Permohonan $permohonan;

    protected Ump $ump;

    protected KodeAnggaran $leafNode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $this->pjUser = User::where('username', 'deden.salim')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '96.1',
            'nama' => 'Leaf Tes Realisasi',
            'depth' => 1,
            'plafon' => 10000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9301',
            'unit_id' => $unit->id,
            'user_id' => $this->pjUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan Realisasi Tes',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $this->permohonan->id,
            'uraian' => 'Rincian kegiatan realisasi',
            'jumlah_diminta' => 5000000,
            'jumlah_disetujui' => 5000000,
        ]);

        $pencairan = (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0200',
            'tgl_cair' => now()->toDateString(),
        ]);

        $this->ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
    }

    /** Ajukan UMP + isi item penggunaan. */
    protected function submitUmp(array $rows = [], string $catatan = ''): void
    {
        $workflow = new UmpWorkflow;
        $workflow->saveItems($this->ump, $rows ?: [
            ['tgl' => now()->toDateString(), 'rincian' => 'Realisasi kegiatan', 'jumlah' => 3000000],
        ]);
        $workflow->submitByPj($this->ump, $this->pjUser, $catatan);
    }

    public function test_full_approval_chain_until_selesai(): void
    {
        $this->submitUmp();
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        $workflow = new UmpWorkflow;

        // 1. ttd_pj -> ttd_manager_keuangan oleh Kadiv
        $workflow->approve($this->ump, $this->kadivUser, ['catatan' => 'Disetujui Kadiv']);
        $this->assertSame('ttd_manager_keuangan', $this->ump->fresh()->status);

        // 2. ttd_manager_keuangan -> ttd_kepala_keuangan oleh Manager Keuangan
        $workflow->approve($this->ump, $this->financeUser, []);
        $this->assertSame('ttd_kepala_keuangan', $this->ump->fresh()->status);

        // 3. Final: wajib scan_final
        try {
            $workflow->approve($this->ump, $this->financeUser, []);
            $this->fail('Approve tanpa scan final seharusnya gagal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('scan fisik ump', strtolower($e->getMessage()));
        }

        $workflow->approve($this->ump, $this->financeUser, [
            'scan_final' => UploadedFile::fake()->create('scan-ump.pdf', 512, 'application/pdf'),
        ]);

        $this->ump->refresh();
        $this->assertSame('selesai', $this->ump->status);
        $this->assertSame('selesai', $this->permohonan->fresh()->status);

        foreach (['ttd_kadiv', 'ttd_manager_keuangan', 'ttd_kepala_keuangan'] as $tahap) {
            $this->assertDatabaseHas('approvals', [
                'approvable_type' => Ump::class,
                'approvable_id' => $this->ump->id,
                'tahap' => $tahap,
                'keputusan' => 'setuju',
            ]);
        }

        $this->assertDatabaseHas('lampiran', [
            'attachable_type' => Ump::class,
            'attachable_id' => $this->ump->id,
            'jenis' => 'scan_final',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'ump.approved.ttd_kepala_keuangan']);

        // Setelah selesai, approve ulang ditolak
        $this->expectException(RuntimeException::class);
        $workflow->approve($this->ump, $this->financeUser, []);
    }

    public function test_budget_tree_reflects_realization_after_selesai(): void
    {
        $this->submitUmp();

        // Sebelum UMP selesai: dana masih terikat, realisasi 0
        $before = (new BudgetCalculator)->calculateTree($this->leafNode->tahun_anggaran_id)
            ->get($this->leafNode->id);
        $this->assertSame(5000000, (int) $before['dana_terikat']);
        $this->assertSame(0, (int) $before['realisasi']);

        $workflow = new UmpWorkflow;
        $workflow->approve($this->ump, $this->kadivUser, []);
        $workflow->approve($this->ump, $this->financeUser, []);
        $workflow->approve($this->ump, $this->financeUser, [
            'scan_final' => UploadedFile::fake()->create('scan.pdf', 256, 'application/pdf'),
        ]);

        $after = (new BudgetCalculator)->calculateTree($this->leafNode->tahun_anggaran_id)
            ->get($this->leafNode->id);

        $this->assertSame('selesai', $this->permohonan->fresh()->status);
        $this->assertSame(0, (int) $after['dana_terikat'], 'Dana terikat harus dibebaskan setelah UMP selesai');
        $this->assertSame(3000000, (int) $after['realisasi'], 'Realisasi = total penggunaan UMP selesai');
        $this->assertSame(7000000, (int) $after['sisa_tersedia'], 'Sisa = plafon - realisasi');
    }

    public function test_monthly_expenditure_report_credits_transaction_month(): void
    {
        $taId = $this->leafNode->tahun_anggaran_id;
        $bulanTransaksi = now()->format('Y-m-d');

        $this->submitUmp([['tgl' => $bulanTransaksi, 'rincian' => 'Belanja bulan berjalan', 'jumlah' => 2500000]]);

        // Sebelum selesai: belum tercatat sebagai realisasi
        $report = (new BudgetCalculator)->getMonthlyExpenditureReport($taId);
        $leafRow = $report->firstWhere('id', $this->leafNode->id);
        $this->assertSame(0, (int) $leafRow['total_pengeluaran']);

        $workflow = new UmpWorkflow;
        $workflow->approve($this->ump, $this->kadivUser, []);
        $workflow->approve($this->ump, $this->financeUser, []);
        $workflow->approve($this->ump, $this->financeUser, [
            'scan_final' => UploadedFile::fake()->create('scan.pdf', 128, 'application/pdf'),
        ]);

        $report = (new BudgetCalculator)->getMonthlyExpenditureReport($taId);
        $leafRow = $report->firstWhere('id', $this->leafNode->id);

        $expectedMonth = (int) now()->format('n');
        $this->assertSame(2500000, (int) $leafRow['months'][$expectedMonth]);
        $this->assertSame(2500000, (int) $leafRow['total_pengeluaran']);
        $this->assertSame(7500000, (int) $leafRow['sisa_anggaran']);

        // Bulan lain nol
        $otherMonth = $expectedMonth === 12 ? 1 : $expectedMonth + 1;
        $this->assertSame(0, (int) $leafRow['months'][$otherMonth]);
    }

    public function test_request_revision_and_cancel(): void
    {
        $workflow = new UmpWorkflow;
        $this->submitUmp();
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        // Minta perbaikan oleh Kadiv
        $workflow->requestRevision($this->ump, $this->kadivUser, 'Rincian belum lengkap, mohon lampirkan nota.');
        $this->assertSame('perlu_perbaikan', $this->ump->fresh()->status);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Ump::class,
            'approvable_id' => $this->ump->id,
            'tahap' => 'revisi',
            'keputusan' => 'perlu_perbaikan',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ump.revision_requested']);

        // UMP perlu_perbaikan dapat diedit ulang lalu diajukan lagi
        $this->ump->refresh();
        $workflow->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Realisasi revisi', 'jumlah' => 3500000],
        ]);
        $workflow->submitByPj($this->ump, $this->pjUser, '');
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        // Batalkan
        $workflow->cancel($this->ump, $this->financeUser, 'Kegiatan dibatalkan pihak unit.');
        $this->assertSame('dibatalkan', $this->ump->fresh()->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Ump::class,
            'approvable_id' => $this->ump->id,
            'tahap' => 'pembatalan',
            'keputusan' => 'batal',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ump.cancelled']);

        // Status final tidak bisa diajukan lagi
        $this->ump->refresh();
        $this->expectException(RuntimeException::class);
        $workflow->submitByPj($this->ump, $this->pjUser, '');
    }

    public function test_request_revision_rejected_on_wrong_status(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('proses persetujuan');

        (new UmpWorkflow)->requestRevision($this->ump, $this->kadivUser, 'alasan tes');
    }

    public function test_diperiksa_oleh_can_be_approved_by_tu_staff(): void
    {
        $this->submitUmp();
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        $tuUser = User::where('username', 'subakti')->firstOrFail();
        $this->assertTrue((bool) $tuUser->is_petugas_input);

        (new UmpWorkflow)->approve($this->ump, $tuUser, ['catatan' => 'Diperiksa oleh TU']);

        $this->assertSame('ttd_manager_keuangan', $this->ump->fresh()->status);
    }

    public function test_diperiksa_oleh_can_be_approved_by_manager_pj(): void
    {
        // Buat UMP dengan staf sebagai PJ (bukan manager)
        $staffUser = User::where('username', 'subakti')->firstOrFail();
        $this->ump->pj_id = $staffUser->id;
        $this->ump->save();

        $workflow = new UmpWorkflow;
        $workflow->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Pengeluaran staf', 'jumlah' => 1000000],
        ]);
        $workflow->submitByPj($this->ump, $staffUser, '');
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        // Manager dari unit PJ (deden.salim) menyetujui tahap Diperiksa Oleh
        $workflow->approve($this->ump, $this->pjUser, ['catatan' => 'Diperiksa Manager Unit']);

        $this->assertSame('ttd_manager_keuangan', $this->ump->fresh()->status);
    }

    public function test_diperiksa_oleh_cannot_be_approved_by_finance_user(): void
    {
        $this->submitUmp();
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Diperiksa Oleh');

        // Keuangan biasa tidak boleh memeriksa tahap Diperiksa Oleh unit
        (new UmpWorkflow)->approve($this->ump, $this->financeUser, []);
    }

    public function test_diperiksa_oleh_cannot_be_approved_by_pj_themselves(): void
    {
        $this->submitUmp();
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Diperiksa Oleh');

        // PJ tidak boleh memeriksa dokumennya sendiri
        (new UmpWorkflow)->approve($this->ump, $this->pjUser, []);
    }

    public function test_diperiksa_oleh_cannot_be_approved_by_other_division(): void
    {
        $this->submitUmp();
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        // User dari divisi lain (DIV-FND)
        $otherDivManager = User::where('username', 'manager.fundraising.int')->firstOrFail();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Diperiksa Oleh');

        (new UmpWorkflow)->approve($this->ump, $otherDivManager, []);
    }
}
