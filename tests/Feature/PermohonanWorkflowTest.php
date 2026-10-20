<?php

namespace Tests\Feature;

use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\BudgetCalculator;
use App\Services\PermohonanWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermohonanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected User $managerUser;

    protected User $kadivUser;

    protected User $financeUser;

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

        $this->staffUser = User::where('username', 'tu.sekretariat')->first();
        $this->managerUser = User::where('username', 'deden.salim')->first();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->first();
        $this->financeUser = User::where('username', 'rochadi.kohar')->first();

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

    public function test_approval_workflow_through_supervision_kadiv_and_keuangan(): void
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

        // Tahap 1: Disetujui Manager Atasan
        $workflow->approve($permohonan, $this->managerUser, ['catatan' => 'Disetujui atasan']);
        $this->assertEquals('disetujui_atasan', $permohonan->fresh()->status);

        // Tahap 2: Diketahui Kepala Divisi
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Diketahui Kepala Divisi']);
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
}
