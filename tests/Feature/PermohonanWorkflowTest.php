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
use Illuminate\Support\Facades\Gate;
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
            'user_id' => $this->managerUser->id,
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
            'jumlah_diminta' => 5000000,
        ]);

        $submitted = $workflow->submit($permohonan);

        $this->assertEquals('diajukan', $submitted->status);
        $this->assertEquals(5000000, $submitted->total_diminta);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertEquals($this->kadivUser->id, $submitted->atasan_id);

        // Format nomor pengajuan: UNT-SEK-KLM-PMH-2026-0001
        $expectedNo = sprintf('UNT-SEK-KLM-PMH-%d-0001', $this->ta->tahun);
        $this->assertEquals($expectedNo, $submitted->no_pengajuan);

        // Leveling mengikuti jabatan pemohon (Manager = urutan 2)
        $this->assertNotNull($submitted->leveling_required_id);
        $lvl = Leveling::find($submitted->leveling_required_id);
        $this->assertEquals(2, $lvl->urutan);
        $this->assertEquals('Manager', $lvl->nama);
    }

    public function test_submit_blocked_when_total_exceeds_pemohon_level_limit(): void
    {
        $workflow = new PermohonanWorkflow;

        // Supervisor batas_atas = 2.000.000; submit 3.000.000 -> must throw
        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id, // urutan=1, batas_atas=2000000
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Test Limit',
            'nama_kegiatan' => 'Pengajuan Melebihi Limit Supervisor',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item melebihi limit',
            'jumlah_diminta' => 3000000,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/melebihi batas kewenangan/');
        $workflow->submit($permohonan);
    }

    public function test_submit_allowed_when_total_at_or_below_pemohon_level_limit(): void
    {
        $workflow = new PermohonanWorkflow;

        // Manager batas_atas = 10.000.000; submit 8.000.000 -> passes
        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id, // urutan=2, batas_atas=10000000
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Test',
            'nama_kegiatan' => 'Pengajuan Dalam Limit Manager',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item dalam limit',
            'jumlah_diminta' => 8000000,
        ]);

        $result = $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $result->status);
    }

    public function test_approval_workflow_through_kadiv_sekretariat_and_keuangan(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
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
            'jumlah_diminta' => 8000000, // Rp 8.000.000 -> dalam batas Manajer (<= 10jt)
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Tahap 1: Diperiksa Kepala Divisi
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Diketahui Kepala Divisi']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Tahap 2: Disetujui Kadiv Keuangan
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$item->id => 7500000],
            'catatan' => 'Disetujui keuangan nominal disesuaikan',
        ]);

        $approvedFinance = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approvedFinance->status);
        $this->assertEquals(7500000, $approvedFinance->total_disetujui);
        $this->assertEquals(7500000, $item->fresh()->jumlah_disetujui);
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

        // Siklus persetujuan bertambah saat submit ulang
        $this->assertEquals(1, $permohonan->approvals()->where('keputusan', 'perlu_perbaikan')->first()->siklus);
        $workflow->approve($permohonan, $this->managerUser, ['catatan' => 'Sudah diperbaiki']);
        $this->assertEquals(2, $permohonan->approvals()->where('keputusan', 'setuju')->latest('id')->first()->siklus);
    }

    public function test_approved_permohonan_binds_budget_in_calculator(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
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

        // Disetujui Kadiv Divisi
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
            'user_id' => $this->supervisorUser->id,
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
            'jumlah_diminta' => 1500000, // Supervisor <= 2jt
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals(1, $permohonan->fresh()->levelingRequired->urutan);

        // Supervisor mencoba menyetujui -> tidak berwenang
        try {
            $workflow->approve($permohonan, $this->supervisorUser);
            $this->fail('Supervisor seharusnya tidak berwenang memeriksa tahap diajukan.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak dapat menyetujui', strtolower($e->getMessage()));
        }

        // Kadiv mencoba melewati Manajer Unit pada tahap 1 Supervisor -> tidak berwenang
        try {
            $workflow->approve($permohonan, $this->kadivUser);
            $this->fail('Kadiv seharusnya tidak dapat melewati Manajer Unit pada tahap 1 Supervisor.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak berwenang', $e->getMessage());
        }

        // Manajer unit lain (Humas) dalam divisi yang sama -> tidak berwenang
        $otherUnitManager = User::where('username', 'sigit.nugroho')->firstOrFail();
        try {
            $workflow->approve($permohonan, $otherUnitManager);
            $this->fail('Manajer unit lain seharusnya tidak berwenang menyetujui tahap 1 unit Kelembagaan.');
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
            'user_id' => $this->managerUser->id,
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
            'jumlah_diminta' => 6000000, // Manager <= 10jt
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals(2, $permohonan->fresh()->levelingRequired->urutan);

        // Supervisor mencoba menyetujui -> gagal / exception
        try {
            $workflow->approve($permohonan, $this->supervisorUser);
            $this->fail('Supervisor tidak berwenang menyetujui pengajuan tingkat Manager.');
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
            'user_id' => $this->managerUser->id,
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
            'jumlah_diminta' => 1500000, // Meski nominal <= 2jt, pemohon Manajer tetap ke Kadiv Divisi!
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);
        $this->assertEquals($this->kadivUser->id, $permohonan->fresh()->atasan_id);

        // Manager lain mencoba menyetujui di tahap diajukan -> ditolak (butuh Kadiv)
        $otherManager = User::where('username', 'sigit.nugroho')->firstOrFail();
        try {
            $workflow->approve($permohonan, $otherManager);
            $this->fail('Manager seharusnya tidak berwenang memeriksa pengajuan Manajer.');
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
            'user_id' => $this->managerUser->id,
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
        // Pencairan PPD: PJ otomatis Manajer unit
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

        // Kadiv membuat permohonan dana (termasuk nominal <= 2jt harus tetap langsung ke Kadiv Keuangan!)
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
            'jumlah_diminta' => 1500000,
        ]);

        // Submit oleh Kadiv
        $submitted = $workflow->submit($permohonan);

        // Tahap 1 dilewati -> atasan_id null, status langsung diketahui_kadiv menunggu Kadiv Keuangan
        $this->assertEquals('diketahui_kadiv', $submitted->status);
        $this->assertNull($submitted->atasan_id);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $submitted->id,
            'tahap' => 'kadiv',
            'keputusan' => 'setuju',
            'approver_id' => $this->kadivUser->id,
        ]);

        // Kadiv tidak dapat menyetujui pengajuannya sendiri di tahap akhir
        try {
            $workflow->approve($submitted, $this->kadivUser);
            $this->fail('Kadiv pemohon seharusnya tidak bisa menyetujui tahap akhir.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak dapat menyetujui permohonannya sendiri', $e->getMessage());
        }

        // Langsung disetujui oleh Kadiv Keuangan
        $approvedFinance = $workflow->approve($submitted, $this->financeUser, ['items' => [$item->id => 1500000]]);
        $this->assertEquals('disetujui_keuangan', $approvedFinance->status);
    }

    public function test_supervisor_level_approval_chain(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
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
        $this->assertEquals($this->managerUser->id, $permohonan->fresh()->atasan_id);

        // Step 1: Manajer Unit menyetujui
        $workflow->approve($permohonan, $this->managerUser, ['catatan' => 'Disetujui Manajer Unit']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Kadiv Keuangan tidak boleh mengambil alih persetujuan akhir rantai Supervisor divisi lain
        try {
            $workflow->approve($permohonan, $this->financeUser);
            $this->fail('Kadiv Keuangan seharusnya tidak berwenang pada tahap akhir Supervisor divisi lain.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak berwenang', $e->getMessage());
        }

        // Step 2: Kadiv Divisi menyetujui tahap akhir (boleh menyesuaikan nominal per item)
        $workflow->approve($permohonan, $this->kadivUser, [
            'items' => [$item->id => 1250000],
            'catatan' => 'Disetujui Kadiv Divisi',
        ]);
        $approved = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(1250000, $approved->total_disetujui);
        $this->assertEquals(1250000, $item->fresh()->jumlah_disetujui);
    }

    public function test_manager_level_approval_chain(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
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
            'jumlah_diminta' => 1800000, // Nominal kecil (<=2jt) tapi pemohon Manajer -> Kadiv Divisi -> Kadiv Keuangan
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);
        $this->assertEquals($this->kadivUser->id, $permohonan->fresh()->atasan_id);

        // Step 1: Kadiv Divisi menyetujui
        $workflow->approve($permohonan, $this->kadivUser, ['catatan' => 'Disetujui Kadiv Divisi']);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Step 2: Kadiv Keuangan menyetujui dengan per-item amounts
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$item->id => 1700000],
            'catatan' => 'Disetujui Kadiv Keuangan dengan penyesuaian',
        ]);
        $approved = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(1700000, $approved->total_disetujui);
        $this->assertEquals(1700000, $item->fresh()->jumlah_disetujui);
    }

    public function test_manager_keuangan_skips_duplicate_step_to_kadiv_keuangan(): void
    {
        $workflow = new PermohonanWorkflow;
        $managerKeu = User::where('username', 'rochadi.kohar')->firstOrFail();

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $managerKeu->unit_id,
            'user_id' => $managerKeu->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Keuangan',
            'nama_kegiatan' => 'Audit Sistem Keuangan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Jasa Audit',
            'jumlah_diminta' => 5000000,
        ]);

        $submitted = $workflow->submit($permohonan);
        $this->assertEquals('diketahui_kadiv', $submitted->status);
        $this->assertNull($submitted->atasan_id);

        $approved = $workflow->approve($submitted, $this->financeUser, ['items' => [$item->id => 4500000]]);
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(4500000, $approved->total_disetujui);
    }

    public function test_kadiv_keuangan_submission_auto_approves_directly(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->financeUser->unit_id,
            'user_id' => $this->financeUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Strategis Keuangan',
            'nama_kegiatan' => 'Pengadaan Sistem Treasury',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Lisensi Perbankan',
            'jumlah_diminta' => 15000000,
        ]);

        $submitted = $workflow->submit($permohonan);

        $this->assertEquals('disetujui_keuangan', $submitted->status);
        $this->assertNull($submitted->atasan_id);
        $this->assertEquals(15000000, $submitted->total_disetujui);
        $this->assertEquals(15000000, $item->fresh()->jumlah_disetujui);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $submitted->id,
            'tahap' => 'keuangan',
            'keputusan' => 'setuju',
            'approver_id' => $this->financeUser->id,
        ]);
    }

    public function test_submit_blocked_when_required_approver_missing(): void
    {
        $workflow = new PermohonanWorkflow;

        // Nonaktifkan seluruh Manajer Unit Kelembagaan (simulasi unit belum punya Manajer)
        User::where('unit_id', $this->unitKlm->id)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', 2))
            ->update(['is_active' => false]);

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Operasional',
            'nama_kegiatan' => 'Tes Approver Hilang',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Tes',
            'jumlah_diminta' => 500000,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Manajer Unit/');
        $workflow->submit($permohonan);
    }

    public function test_resubmit_to_kadiv_clears_stale_atasan_id(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Tes Resubmit Bersih Atasan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item',
            'jumlah_diminta' => 1000000,
        ]);

        $workflow->submit($permohonan);
        $this->assertEquals($this->managerUser->id, $permohonan->fresh()->atasan_id);

        $workflow->requestRevision($permohonan, $this->managerUser, 'Alihkan ke pengajuan Kadiv');

        // Alihkan pemohon ke Kadiv sebelum submit ulang
        $permohonan->update(['user_id' => $this->kadivUser->id]);
        $resubmitted = $workflow->submit($permohonan->fresh());

        $this->assertEquals('diketahui_kadiv', $resubmitted->status);
        $this->assertNull($resubmitted->atasan_id);
    }

    public function test_pengusul_cannot_approve_own_submitted_permohonan(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
            'pengusul_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Tes Pengusul Sama Dengan Approver',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item',
            'jumlah_diminta' => 1000000,
        ]);

        $submitted = $workflow->submit($permohonan);
        $this->assertFalse($workflow->canUserApprove($this->managerUser, $submitted));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/pengusul/i');
        $workflow->approve($submitted, $this->managerUser);
    }

    public function test_admin_cannot_self_approve_but_can_process_others(): void
    {
        $workflow = new PermohonanWorkflow;
        $admin = User::where('username', 'admin')->firstOrFail();

        $own = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $admin->id,
            'pengusul_id' => $admin->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Pengajuan Admin Sendiri',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $own->id,
            'uraian' => 'Kebutuhan Admin',
            'jumlah_diminta' => 1000000,
        ]);

        $this->actingAs($admin);
        $submittedOwn = $workflow->submit($own);

        $this->assertFalse($workflow->canUserApprove($admin, $submittedOwn));
        $this->assertFalse(Gate::forUser($admin)->allows('approveKadiv', $submittedOwn));
        $this->assertFalse(Gate::forUser($admin)->allows('approveKeuangan', $submittedOwn));

        foreach (['approve', 'requestRevision', 'reject'] as $method) {
            try {
                if ($method === 'approve') {
                    $workflow->approve($submittedOwn->fresh(), $admin);
                } elseif ($method === 'requestRevision') {
                    $workflow->requestRevision($submittedOwn->fresh(), $admin, 'Coba revisi sendiri');
                } else {
                    $workflow->reject($submittedOwn->fresh(), $admin, 'Coba tolak sendiri');
                }
                $this->fail("Expected RuntimeException for admin self {$method}.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsStringIgnoringCase('pengusul', $e->getMessage());
            }
        }

        // Approver sah tetap dapat memproses, dan Admin dapat memproses pengajuan orang lain
        $this->assertTrue($workflow->canUserApprove($this->managerUser, $submittedOwn));

        $other = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
            'pengusul_id' => $this->supervisorUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Pengajuan Supervisor',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);
        PermohonanItem::create([
            'permohonan_id' => $other->id,
            'uraian' => 'Item',
            'jumlah_diminta' => 500000,
        ]);

        $this->actingAs($this->supervisorUser);
        $submittedOther = $workflow->submit($other);
        $this->assertTrue($workflow->canUserApprove($admin, $submittedOther));
    }

    public function test_in_flight_permohonan_keeps_original_leveling_chain_after_submitter_promotion(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
            'pengusul_id' => $this->supervisorUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Promosi In-Flight',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Promosi',
            'jumlah_diminta' => 1000000,
        ]);

        $this->actingAs($this->supervisorUser);
        $submitted = $workflow->submit($permohonan);
        $this->assertEquals('diajukan', $submitted->status);

        // Simulasikan pemohon dipromosikan menjadi Kadiv Keuangan saat dokumen masih berjalan
        $kadivLevel = Leveling::where('urutan', 3)->firstOrFail();
        $this->supervisorUser->update([
            'role' => 'keuangan',
            'leveling_id' => $kadivLevel->id,
            'unit_id' => $this->financeUser->unit_id,
        ]);

        $reloaded = $submitted->fresh();
        $chain = $workflow->resolveApprovalChainForPermohonan($reloaded);
        $this->assertEquals('supervisor', $chain['mode']);
        $this->assertFalse($chain['auto_approve']);
        $this->assertTrue($workflow->canUserApprove($this->managerUser, $reloaded));

        $step1Done = $workflow->approve($reloaded, $this->managerUser);
        $this->assertEquals('diketahui_kadiv', $step1Done->status);
        $this->assertTrue($workflow->canUserApprove($this->kadivUser, $step1Done));

        $finalDone = $workflow->approve($step1Done, $this->kadivUser);
        $this->assertEquals('disetujui_keuangan', $finalDone->status);
    }

    public function test_kadiv_multi_cycle_revision_and_resubmit_reaches_cycle_three_cleanly(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'pengusul_id' => $this->kadivUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Divisi',
            'nama_kegiatan' => 'Tes Multi Siklus Kadiv',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Besar',
            'jumlah_diminta' => 15000000,
        ]);

        $this->actingAs($this->kadivUser);
        $s1 = $workflow->submit($permohonan);
        $this->assertEquals('diketahui_kadiv', $s1->status);
        $this->assertEquals(1, $workflow->getCurrentCycle($s1));

        $r1 = $workflow->requestRevision($s1, $this->financeUser, 'Revisi 1');
        $this->assertEquals('perlu_perbaikan', $r1->status);

        $s2 = $workflow->submit($r1->fresh());
        $this->assertEquals('diketahui_kadiv', $s2->status);
        $this->assertEquals(2, $workflow->getCurrentCycle($s2));

        $r2 = $workflow->requestRevision($s2, $this->financeUser, 'Revisi 2');
        $this->assertEquals('perlu_perbaikan', $r2->status);

        $s3 = $workflow->submit($r2->fresh());
        $this->assertEquals('diketahui_kadiv', $s3->status);
        $this->assertEquals(3, $workflow->getCurrentCycle($s3));

        $approved = $workflow->approve($s3, $this->financeUser);
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $approved->id,
            'siklus' => 3,
            'tahap' => 'keuangan',
            'keputusan' => 'setuju',
        ]);
    }

    public function test_tu_submission_for_kadiv_keuangan_requires_confirmation_not_auto_approve(): void
    {
        $workflow = new PermohonanWorkflow;

        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'unit_id' => $this->financeUser->unit_id,
            'user_id' => $this->financeUser->id,
            'pengusul_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Keuangan',
            'nama_kegiatan' => 'Diajukan TU Untuk Kadiv Keuangan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Audit Sistem',
            'jumlah_diminta' => 12000000,
        ]);

        $this->actingAs($this->staffUser);
        $submitted = $workflow->submit($permohonan);

        $this->assertEquals('diketahui_kadiv', $submitted->status);
        $this->assertNull($submitted->total_disetujui);
        $this->assertFalse($workflow->canUserApprove($this->staffUser, $submitted));
        $this->assertTrue($workflow->canUserApprove($this->financeUser, $submitted));

        $confirmed = $workflow->approve($submitted, $this->financeUser);
        $this->assertEquals('disetujui_keuangan', $confirmed->status);
        $this->assertEquals(12000000, $confirmed->total_disetujui);
    }

    public function test_legacy_in_flight_backfill_migration_repairs_amount_based_leveling_required(): void
    {
        $workflow = new PermohonanWorkflow;
        $spvLevel = Leveling::where('urutan', 1)->firstOrFail();
        $mgrLevel = Leveling::where('urutan', 2)->firstOrFail();
        $kadivLevel = Leveling::where('urutan', 3)->firstOrFail();

        // Simulasikan data in-flight legacy sebelum deploy: nominal 15 jt oleh Supervisor tercatat leveling 3
        $legacySpv = Permohonan::create([
            'no_pengajuan' => 'LEG-SPV-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->supervisorUser->id,
            'pengusul_id' => $this->supervisorUser->id,
            'leveling_required_id' => $kadivLevel->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Legacy',
            'nama_kegiatan' => 'Legacy Supervisor Nominal Besar',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'diajukan',
            'total_diminta' => 15000000,
        ]);
        PermohonanItem::create([
            'permohonan_id' => $legacySpv->id,
            'uraian' => 'Item Legacy SPV',
            'jumlah_diminta' => 15000000,
        ]);

        // Simulasikan data in-flight legacy sebelum deploy: nominal <= 2 jt oleh Manajer tercatat leveling 1
        $legacyMgr = Permohonan::create([
            'no_pengajuan' => 'LEG-MGR-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'pengusul_id' => $this->managerUser->id,
            'leveling_required_id' => $spvLevel->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Legacy',
            'nama_kegiatan' => 'Legacy Manajer Nominal Kecil',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'status' => 'diajukan',
            'total_diminta' => 1000000,
        ]);
        PermohonanItem::create([
            'permohonan_id' => $legacyMgr->id,
            'uraian' => 'Item Legacy MGR',
            'jumlah_diminta' => 1000000,
        ]);

        // Sebelum migrasi pun defense-in-depth status 'diajukan' sudah merutekan Supervisor 15 jt ke Manajer Unit
        $this->assertTrue($workflow->canUserApprove($this->managerUser, $legacySpv));

        $migrationFiles = glob(database_path('migrations/*_backfill_permohonan_leveling_required_from_submitter.php'));
        $this->assertNotEmpty($migrationFiles);
        $migration = require $migrationFiles[0];
        $migration->up();

        $legacySpv->refresh();
        $legacyMgr->refresh();

        $this->assertEquals($spvLevel->id, $legacySpv->leveling_required_id);
        $this->assertEquals($mgrLevel->id, $legacyMgr->leveling_required_id);

        // Kedua dokumen legacy menyelesaikan rantai persetujuan sesuai jabatan pemohon
        $spvStep1 = $workflow->approve($legacySpv, $this->managerUser);
        $this->assertEquals('diketahui_kadiv', $spvStep1->status);
        $spvFinal = $workflow->approve($spvStep1, $this->kadivUser);
        $this->assertEquals('disetujui_keuangan', $spvFinal->status);

        $mgrStep1 = $workflow->approve($legacyMgr, $this->kadivUser);
        $this->assertEquals('diketahui_kadiv', $mgrStep1->status);
        $mgrFinal = $workflow->approve($mgrStep1, $this->financeUser);
        $this->assertEquals('disetujui_keuangan', $mgrFinal->status);
    }
}
