<?php

namespace Tests\Feature;

use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\BudgetCalculator;
use App\Services\PermohonanWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PermohonanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected User $managerUser;

    protected User $kadivUser;

    protected User $financeUser;

    protected User $supervisorUser;

    protected TahunAnggaran $ta;

    protected Unit $unitKlm;

    protected KodeAnggaran $leafNode;

    protected SumberDana $sumberDana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->staffUser = User::where('username', 'subakti')->first();
        $this->managerUser = User::where('username', 'deden.salim')->first();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->first();
        $this->financeUser = User::where('username', 'direktur.keuangan')->first();
        $this->supervisorUser = User::where('username', 'anas.priyogi')->first();

        $this->ta = TahunAnggaran::where('status', 'aktif')->first();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->first();
        $this->sumberDana = SumberDana::first();

        // Buat sample kode anggaran berjenjang
        $root = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '2',
            'nama' => 'Penyaluran',
            'depth' => 1,
            'plafon' => 100000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $root->id,
            'kode' => '2.1',
            'nama' => 'Penyaluran Zakat Fakir Miskin',
            'depth' => 2,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    public function test_submit_generates_unique_auto_numbering_and_sets_leveling(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Santunan Yatim Piatu',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Paket Sembako & Uang Santunan',
            'jumlah_diminta' => 5000000, // Rp 5.000.000 -> Leveling 2 (Manager)
        ]);

        $submitted = $workflow->submit($permohonan);

        $this->assertEquals('diajukan', $submitted->status);
        $this->assertEquals(5000000, $submitted->total_diminta);
        $this->assertNotNull($submitted->submitted_at);

        // Format nomor pengajuan: UNT-SEK-KLM-PMH-2026-0001
        $expectedNo = sprintf('UNT-SEK-KLM-PMH-%d-0001', $this->ta->tahun);
        $this->assertEquals($expectedNo, $submitted->no_pengajuan);

        // Leveling harus manager (urutan 2: 2jt s.d 10jt)
        $this->assertNotNull($submitted->leveling_required_id);
        $lvl = Leveling::find($submitted->leveling_required_id);
        $this->assertEquals(2, $lvl->urutan);
        $this->assertEquals('Manager', $lvl->nama);
    }

    public function test_approval_workflow_through_kadiv_sekretariat_and_keuangan(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Dakwah',
            'nama_kegiatan' => 'Tabligh Akbar',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Honor Da\'i dan Konsumsi',
            'jumlah_diminta' => 15000000, // Rp 15.000.000 -> Leveling 3 (Kepala Divisi)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Tahap 2: Diperiksa Kepala Divisi Sekretariat
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Diketahui Kepala Divisi Sekretariat']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Tahap 3: Disetujui Keuangan
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$item->id => 14000000], // Disetujui Rp 14.000.000
            'catatan' => 'Disetujui keuangan nominal disesuaikan',
        ]);

        $approvedFinance = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approvedFinance->status);
        $this->assertEquals(14000000, $approvedFinance->total_disetujui);
        $this->assertEquals(14000000, $item->fresh()->jumlah_disetujui);
    }

    public function test_revision_and_cycle_increment(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan Sosial',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Biaya Awal',
            'jumlah_diminta' => 2000000,
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Minta perbaikan
        $workflow->requestRevision($permohonan, $this->managerUser, 'Mohon lengkapi rincian harga satuan.');
        $this->assertEquals('perlu_perbaikan', $permohonan->fresh()->status);

        // Pengaju mengajukan ulang
        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Siklus persetujuan bertambah
        $this->assertEquals(1, $permohonan->approvals()->where('keputusan', 'perlu_perbaikan')->first()->siklus);
    }

    public function test_approved_permohonan_binds_budget_in_calculator(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Penyaluran Zakat',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Penyaluran Langsung',
            'jumlah_diminta' => 10000000,
        ]);

        $workflow->submit($permohonan);

        // Langsung disetujui Kadiv (skip atasan karena Kadiv)
        $workflow->approve($permohonan, $this->kadivUser);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Disetujui Keuangan
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$item->id => 10000000],
        ]);

        // Verifikasi kalkulasi dana terikat pada BudgetCalculator
        $calculator = new BudgetCalculator;
        $tree = $calculator->calculateTree($this->ta->id);

        $leafCalc = $tree->get($this->leafNode->id);
        $this->assertEquals(10000000, $leafCalc['dana_terikat']);
        $this->assertEquals(40000000, $leafCalc['sisa_tersedia']); // 50jt - 10jt = 40jt

        // Rollup ke node root
        $rootNode = KodeAnggaran::find($this->leafNode->parent_id);
        $rootCalc = $tree->get($rootNode->id);
        $this->assertEquals(10000000, $rootCalc['dana_terikat']);
        $this->assertEquals(90000000, $rootCalc['sisa_tersedia']); // 100jt - 10jt = 90jt
    }

    public function test_stage_two_is_reserved_for_manager_or_kadiv(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Operasional Kecil',
            'nama_kegiatan' => 'Beli Alat Tulis Kantor',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Kertas HVS dan Spidol',
            'jumlah_diminta' => 1500000, // Rp 1.500.000 (Supervisor <= 2jt)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals(1, $permohonan->fresh()->levelingRequired->urutan);

        // Supervisor mencoba menyetujui -> tidak berwenang
        try {
            $workflow->approve($permohonan, $this->supervisorUser);
            $this->fail('Supervisor seharusnya tidak berwenang memeriksa tahap diajukan.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak berwenang', $e->getMessage());
        }

        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        $workflow->approve($permohonan, $this->managerUser, ['catatan' => 'Disetujui Manajer Unit']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);
    }

    public function test_between_2_and_10_million_rejected_for_supervisor_but_approved_by_kadiv(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Kegiatan Sedang',
            'nama_kegiatan' => 'Pelatihan Staff',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Honor Pemateri Pelatihan',
            'jumlah_diminta' => 6000000, // Rp 6.000.000 (Manager <= 10jt)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals(2, $permohonan->fresh()->levelingRequired->urutan);

        // Supervisor mencoba menyetujui -> gagal / exception
        try {
            $workflow->approve($permohonan, $this->supervisorUser);
            $this->fail('Supervisor tidak berwenang menyetujui nominal tingkat Manager.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak berwenang', $e->getMessage());
        }

        // Kadiv menyetujui -> berhasil
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Disetujui Kadiv']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);
    }

    public function test_diajukan_for_manager_level_requires_kadiv_approval(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Kegiatan Sedang',
            'nama_kegiatan' => 'Pelatihan Staff 2',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Honor Pemateri Pelatihan',
            'jumlah_diminta' => 6000000, // Rp 6.000.000 (Manager <= 10jt)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Manager mencoba menyetujui urutan 2 di tahap diajukan -> ditolak (butuh Kadiv)
        try {
            $workflow->approve($permohonan, $this->managerUser);
            $this->fail('Manager seharusnya tidak berwenang memeriksa tahap diajukan untuk tingkat Manager.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak berwenang', $e->getMessage());
        }

        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Disetujui Kadiv']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);
    }

    public function test_cairkan_ppd_defaults_pj_to_unit_manager(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id, // TU menginput
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Bantuan',
            'nama_kegiatan' => 'Penyaluran Bantuan Warga',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Bantuan Warga',
            'jumlah_diminta' => 5000000,
        ]);

        $workflow->submit($permohonan);
        $workflow->approve($permohonan, $this->kadivUser);
        $workflow->approve($permohonan, $this->financeUser, ['items' => [$item->id => 5000000]]);
        // Pencairan PPD: PJ otomatis Manajer, bukan staf TU
        $pencairan = $workflow->cairkan($permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/2026/TEST-PJ',
            'tgl_cair' => now()->toDateString(),
        ]);

        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        $this->assertEquals($this->managerUser->id, $ump->pj_id);
        $this->assertNotEquals($this->staffUser->id, $ump->pj_id);
    }

    public function test_kadiv_created_permohonan_auto_completes_tahap_2(): void
    {
        $workflow = new PermohonanWorkflow;

        // Kadiv membuat permohonan dana
        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Strategis Kadiv',
            'nama_kegiatan' => 'Rakor Pimpinan Lembaga',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Konsumsi & Akomodasi Rakor',
            'jumlah_diminta' => 15000000,
        ]);

        // Submit oleh Kadiv
        $submitted = $workflow->submit($permohonan);

        // Tahap 1 & Tahap 2 otomatis selesai -> status langsung diketahui_kadiv
        $this->assertEquals('diketahui_kadiv', $submitted->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $submitted->id,
            'tahap' => 'kadiv',
            'keputusan' => 'setuju',
            'approver_id' => $this->kadivUser->id,
        ]);

        // Langsung dapat disetujui oleh Bagian Keuangan (Tahap 3) tanpa perlu atasan/kadiv lagi
        $approvedFinance = $workflow->approve($submitted, $this->financeUser, ['items' => [$item->id => 15000000]]);
        $this->assertEquals('disetujui_keuangan', $approvedFinance->status);
    }

    public function test_supervisor_level_approval_chain(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Operasional',
            'nama_kegiatan' => 'Konsumsi Rapat Bulanan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Snack dan Minum Rapat',
            'jumlah_diminta' => 1500000, // Supervisor level (<= 2jt)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);
        $this->assertEquals(1, $permohonan->fresh()->levelingRequired->urutan);

        // Step 1: Manajer Unit (divisi sama) menyetujui
        $workflow->approve($permohonan, $this->managerUser, ['catatan' => 'Disetujui Manajer Unit']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Step 2: Kadiv Divisi (divisi sama) menyetujui tahap akhir
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Disetujui Kadiv Divisi']);
        $approved = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(1500000, $approved->total_disetujui);
        $this->assertEquals(1500000, $item->fresh()->jumlah_disetujui);
    }

    public function test_manager_level_approval_chain(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Pelatihan',
            'nama_kegiatan' => 'Workshop IT',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Sewa Tempat & Perlengkapan',
            'jumlah_diminta' => 6000000, // Manager level (2.1 - 10jt)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);
        $this->assertEquals(2, $permohonan->fresh()->levelingRequired->urutan);

        // Step 1: Kadiv Divisi menyetujui
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Disetujui Kadiv Divisi']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Step 2: Kadiv Keuangan menyetujui dengan per-item amounts
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$item->id => 5500000],
            'catatan' => 'Disetujui Kadiv Keuangan dengan penyesuaian',
        ]);
        $approved = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(5500000, $approved->total_disetujui);
        $this->assertEquals(5500000, $item->fresh()->jumlah_disetujui);
    }
}
