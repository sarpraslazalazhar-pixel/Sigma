<?php

namespace Tests\Feature;

use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Models\Asnaf;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\BudgetCalculator;
use App\Services\PermohonanWorkflow;
use Database\Seeders\KodeAnggaranSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermohonanItGaApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $koordinatorIt;

    protected User $managerHumas;

    protected User $kadivUser;

    protected User $financeUser;

    protected User $tuSekretariat;

    protected TahunAnggaran $ta;

    protected Unit $unitHum;

    protected KodeAnggaran $posSaranaIt;

    protected KodeAnggaran $posSistemIt;

    protected SumberDana $sumberDanaZakat;

    protected Asnaf $asnafAmil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
            KodeAnggaranSeeder::class,
        ]);

        $this->koordinatorIt = User::where('username', 'koordinator.it')->firstOrFail();
        $this->managerHumas = User::where('username', 'manager.humas')->firstOrFail();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->tuSekretariat = User::where('username', 'tu.sekretariat')->firstOrFail();

        $this->ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $this->unitHum = Unit::where('kode', 'UNT-SEK-HUM')->firstOrFail();
        $this->unitIt = Unit::where('kode', 'UNT-SEK-HUM')->firstOrFail();

        $this->posSaranaIt = KodeAnggaran::where('tahun_anggaran_id', $this->ta->id)
            ->where('kode', '2.4.2.12')
            ->firstOrFail();

        $this->posSistemIt = KodeAnggaran::where('tahun_anggaran_id', $this->ta->id)
            ->where('kode', '2.4.2.13')
            ->firstOrFail();

        $this->sumberDanaZakat = SumberDana::where('nama', 'like', '%Zakat%')->firstOrFail();
        $this->asnafAmil = Asnaf::where('nama', 'Amil')->firstOrFail();
    }

    public function test_create_component_auto_selects_manager_and_zakat_for_pos_2_4(): void
    {
        Livewire::actingAs($this->koordinatorIt)
            ->test(PermohonanCreate::class)
            ->assertSet('unit_id', $this->unitIt->id)
            ->assertSet('atasan_id', $this->managerHumas->id)
            ->set('kode_anggaran_id', $this->posSaranaIt->id)
            ->assertSet('sumber_dana_id', $this->sumberDanaZakat->id);
    }

    public function test_full_approval_flow_it_ga_to_budget_calculator(): void
    {
        $workflow = new PermohonanWorkflow;

        // 1. Koordinator IT mengajukan pengadaan laptop & server
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-IT-001',
            'unit_id' => $this->unitIt->id,
            'user_id' => $this->koordinatorIt->id,
            'atasan_id' => $this->managerHumas->id,
            'kode_anggaran_id' => $this->posSaranaIt->id,
            'program' => 'Operasional Kesekretariatan',
            'nama_kegiatan' => 'Pengadaan Laptop dan Server IT',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaZakat->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Laptop Core i7 & Server Backup IT',
            'asnaf_id' => $this->asnafAmil->id,
            'jumlah_diminta' => 25000000,
        ]);

        $workflow->submit($permohonan);
        $permohonan->refresh();

        $this->assertEquals('diajukan', $permohonan->status);
        $this->assertStringStartsWith('UNT-SEK-HUM-PMH-', $permohonan->no_pengajuan);

        // 2. Tahap 1: Manager Humas IT GA menyetujui
        $workflow->approve($permohonan, $this->managerHumas, [
            'catatan' => 'Kebutuhan sarana kerja IT disetujui atasan divisi',
        ]);
        $permohonan->refresh();
        $this->assertEquals('disetujui_atasan', $permohonan->status);

        // 3. Tahap 2: Kadiv (Rahmatullah Sidik) menyetujui
        $workflow->approve($permohonan, $this->kadivUser, [
            'catatan' => 'Disetujui Kadiv Sekretariat / Dirops',
        ]);
        $permohonan->refresh();
        $this->assertEquals('diketahui_kadiv', $permohonan->status);

        // 4. Tahap 3: Keuangan (Rochadi Kohar) menyetujui
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$item->id => 24000000],
            'catatan' => 'Disetujui Keuangan Rp 24.000.000',
        ]);
        $permohonan->refresh();
        $this->assertEquals('disetujui_keuangan', $permohonan->status);
        $this->assertEquals(24000000, $permohonan->total_disetujui);

        // 5. Verifikasi Dana Resmi Terikat di BudgetCalculator
        $calculator = new BudgetCalculator;
        $tree = $calculator->calculateTree($this->ta->id);

        $leafCalc = $tree->get($this->posSaranaIt->id);
        $this->assertNotNull($leafCalc);
        $this->assertEquals(24000000, $leafCalc['dana_terikat']);
        $this->assertEquals($leafCalc['plafon'] - 24000000, $leafCalc['sisa_tersedia']);

        // Rollup ke node 2.4.2 (Biaya Utilitas)
        $node242Calc = $tree->get($this->posSaranaIt->parent_id);
        $this->assertNotNull($node242Calc);
        $this->assertGreaterThanOrEqual(24000000, $node242Calc['dana_terikat']);
    }

    public function test_manager_submission_skips_step_two_to_kadiv_directly(): void
    {
        $workflow = new PermohonanWorkflow;

        // Manager mengajukan
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-MGR-001',
            'unit_id' => $this->unitHum->id,
            'user_id' => $this->managerHumas->id,
            'kode_anggaran_id' => $this->posSistemIt->id,
            'program' => 'Operasional Kesekretariatan',
            'nama_kegiatan' => 'Pengembangan Sistem ERP & Mobile Apps',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaZakat->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Lisensi Software dan Cloud Server',
            'asnaf_id' => $this->asnafAmil->id,
            'jumlah_diminta' => 50000000,
        ]);

        $workflow->submit($permohonan);
        $permohonan->refresh();
        $this->assertEquals('diajukan', $permohonan->status);

        // Disetujui oleh Kadiv langsung -> langsung berstatus 'diketahui_kadiv' (Tahap 2 dilewati)
        $workflow->approve($permohonan, $this->kadivUser, [
            'catatan' => 'Disetujui langsung oleh Kadiv',
        ]);
        $permohonan->refresh();
        $this->assertEquals('diketahui_kadiv', $permohonan->status);

        // Tahap Keuangan melalui petugas input TU Sekretariat
        Livewire::actingAs($this->tuSekretariat)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->set('proxySignerName', 'Rochadi Kohar')
            ->set('proxySignerJabatan', 'Manajer Anggaran & Treasury')
            ->set('proxySignedAt', now()->toDateString())
            ->set("approvedItemAmounts.{$item->id}", 50000000)
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $permohonan->refresh();
        $this->assertEquals('disetujui_keuangan', $permohonan->status);
        $this->assertEquals(50000000, $permohonan->total_disetujui);
    }
}
