<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Laporan\LaporanMonitoringIndex;
use App\Livewire\Master\KodeAnggaranIndex;
use App\Models\AuditLog;
use App\Models\KodeAnggaran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlafonUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $financeUser;

    protected User $staffUser;

    protected TahunAnggaran $ta;

    protected Unit $unit;

    protected SumberDana $sumberDana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->financeUser = User::where('username', 'rochadi.kohar')->first();
        $this->staffUser = User::where('username', 'subakti')->first();

        $this->ta = TahunAnggaran::where('status', 'aktif')->first();
        $this->unit = Unit::where('kode', 'UNT-SEK-KLM')->first();
        $this->sumberDana = SumberDana::first();
    }

    public function test_admin_can_update_plafon_of_disahkan_node_directly(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.1',
            'nama' => 'Pos Uji Plafon Langsung',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openEditModal', $node->id)
            ->set('form_plafon', '60000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee("Plafon pos anggaran {$node->kode} berhasil diperbarui");

        $this->assertSame(60000000, $node->fresh()->plafon);

        $log = AuditLog::where('action', 'kode_anggaran.updated')
            ->where('entity_id', $node->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(50000000, $log->before['plafon']);
        $this->assertEquals(60000000, $log->after['plafon']);
    }

    public function test_plafon_update_below_floor_is_rejected(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.2',
            'nama' => 'Pos Uji Floor',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        // 20M Dana Terikat
        Permohonan::create([
            'no_pengajuan' => 'PMH-TEST-FL-1',
            'unit_id' => $this->unit->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $node->id,
            'program' => 'Program Terikat',
            'nama_kegiatan' => 'Kegiatan Terikat',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'total_diminta' => 20000000,
            'total_disetujui' => 20000000,
            'status' => 'disetujui_keuangan',
        ]);

        // 10M Realisasi (Permohonan selesai + UMP selesai + UmpItem)
        $pmhRealisasi = Permohonan::create([
            'no_pengajuan' => 'PMH-TEST-FL-2',
            'unit_id' => $this->unit->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $node->id,
            'program' => 'Program Realisasi',
            'nama_kegiatan' => 'Kegiatan Realisasi',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'total_diminta' => 10000000,
            'total_disetujui' => 10000000,
            'status' => 'selesai',
        ]);

        $pencairan = Pencairan::create([
            'permohonan_id' => $pmhRealisasi->id,
            'no_ppd' => 'PPD-TEST-FL-01',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 10000000,
        ]);

        $ump = Ump::create([
            'pencairan_id' => $pencairan->id,
            'tgl_terima' => now()->toDateString(),
            'tujuan' => 'Realisasi Uji Floor',
            'pj_id' => $this->staffUser->id,
            'jumlah_total' => 10000000,
            'total_penggunaan' => 10000000,
            'saldo' => 0,
            'status' => 'selesai',
        ]);

        UmpItem::create([
            'ump_id' => $ump->id,
            'tgl' => now()->toDateString(),
            'rincian' => 'Pengeluaran Floor Test',
            'no_bukti' => 'BKT-FL-01',
            'jumlah' => 10000000,
        ]);

        // Floor = 20M + 10M = 30M. Coba turunkan plafon ke 25M
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openEditModal', $node->id)
            ->set('form_plafon', '25000000')
            ->call('save')
            ->assertHasErrors(['form_plafon']);

        $this->assertSame(50000000, $node->fresh()->plafon);
    }

    public function test_plafon_update_at_floor_is_accepted(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.3',
            'nama' => 'Pos Uji At Floor',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        // 30M Dana Terikat
        Permohonan::create([
            'no_pengajuan' => 'PMH-TEST-AT-1',
            'unit_id' => $this->unit->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $node->id,
            'program' => 'Program Terikat 30M',
            'nama_kegiatan' => 'Kegiatan Terikat 30M',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDana->id,
            'total_diminta' => 30000000,
            'total_disetujui' => 30000000,
            'status' => 'disetujui_keuangan',
        ]);

        // Floor = 30M. Set tepat ke 30M
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openEditModal', $node->id)
            ->set('form_plafon', '30000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee("Plafon pos anggaran {$node->kode} berhasil diperbarui");

        $this->assertSame(30000000, $node->fresh()->plafon);
    }

    public function test_plafon_update_exceeding_parent_ceiling_is_rejected(): void
    {
        $parent = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.4',
            'nama' => 'Induk Plafon 100M',
            'depth' => 1,
            'plafon' => 100000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $sibling = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $parent->id,
            'kode' => '8.4.1',
            'nama' => 'Anak 1 Plafon 60M',
            'depth' => 2,
            'plafon' => 60000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $parent->id,
            'kode' => '8.4.2',
            'nama' => 'Anak 2 Plafon 40M',
            'depth' => 2,
            'plafon' => 40000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        // Total anak sekarang 60M + 40M = 100M. Coba naikkan Anak 2 ke 50M (total 110M > 100M)
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openEditModal', $node->id)
            ->set('form_plafon', '50000000')
            ->call('save')
            ->assertHasErrors(['form_plafon']);

        $this->assertSame(40000000, $node->fresh()->plafon);
    }

    public function test_non_admin_cannot_edit_plafon_of_disahkan_node(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.5',
            'nama' => 'Pos Uji Role Guard',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->staffUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openEditModal', $node->id)
            ->set('form_plafon', '60000000')
            ->call('save')
            ->assertHasErrors(['form_plafon']);

        $this->assertSame(50000000, $node->fresh()->plafon);
    }

    public function test_ui_smoke_views_have_no_target_revisi_or_kurang_terserap(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.6',
            'nama' => 'Pos Leaf Smoke UI',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        // 1. /master/kode-anggaran
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->assertOk()
            ->assertDontSee('openTargetModal')
            ->assertDontSee('openRevisionModal')
            ->assertDontSee('openRevisionReviewModal')
            ->assertDontSee('openRevisionHistoryModal')
            ->assertDontSee('Target Bulanan')
            ->assertDontSee('Revisi Pending');

        // 2. /dashboard
        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Aman')
            ->assertSee('Limit')
            ->assertSee('Over Budget')
            ->assertSee('Tanpa Budget')
            ->assertDontSee('Kurang Terserap')
            ->assertDontSee('Target Bulanan');

        // 3. /laporan/monitoring
        Livewire::actingAs($this->adminUser)
            ->test(LaporanMonitoringIndex::class)
            ->assertOk()
            ->assertDontSee('Laporan Exception')
            ->assertDontSee('Riwayat Revisi Anggaran')
            ->assertDontSee('Target Kumulatif')
            ->assertDontSee('Variance');
    }
}
