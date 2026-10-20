<?php

namespace Tests\Feature;

use App\Livewire\Master\KodeAnggaranIndex;
use App\Livewire\Master\TahunAnggaranIndex;
use App\Livewire\Master\UnitDivisiIndex;
use App\Models\KodeAnggaran;
use App\Models\TahunAnggaran;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();

        // Isolated sample kode anggaran for UI tests
        $ta = TahunAnggaran::where('status', 'aktif')->first();
        if ($ta) {
            $node1 = KodeAnggaran::create([
                'tahun_anggaran_id' => $ta->id,
                'kode' => '1',
                'nama' => 'Beban Operasional & Program',
                'depth' => 1,
                'plafon' => 500000000,
                'budget_status' => 'disahkan',
                'is_active' => true,
            ]);

            $node11 = KodeAnggaran::create([
                'tahun_anggaran_id' => $ta->id,
                'parent_id' => $node1->id,
                'kode' => '1.1',
                'nama' => 'Kesekretariatan & Administrasi',
                'depth' => 2,
                'plafon' => 200000000,
                'budget_status' => 'disahkan',
                'is_active' => true,
            ]);

            KodeAnggaran::create([
                'tahun_anggaran_id' => $ta->id,
                'parent_id' => $node11->id,
                'kode' => '1.1.1',
                'nama' => 'Operasional Kantor & IT',
                'depth' => 3,
                'plafon' => 50000000,
                'budget_status' => 'disahkan',
                'is_active' => true,
            ]);
        }
    }

    public function test_can_render_all_master_pages(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/master/kode-anggaran')
            ->assertStatus(200)
            ->assertSee('Master Kode Anggaran')
            ->assertSee('1.1.1');

        $this->actingAs($this->adminUser)
            ->get('/master/tahun-anggaran')
            ->assertStatus(200)
            ->assertSee('Kelola Tahun Anggaran')
            ->assertSee('TA 2026');

        $this->actingAs($this->adminUser)
            ->get('/master/unit-divisi')
            ->assertStatus(200)
            ->assertSee('Master Divisi & Unit', false)
            ->assertSee('Divisi Sekretariat');
    }

    public function test_can_add_new_kode_anggaran(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->first();

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openCreateModal')
            ->set('form_kode', '2')
            ->set('form_nama', 'Program Baru 2026')
            ->set('form_plafon', '150000000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kode_anggaran', [
            'tahun_anggaran_id' => $ta->id,
            'kode' => '2',
            'nama' => 'Program Baru 2026',
            'plafon' => 150000000,
            'depth' => 1,
        ]);
    }

    public function test_cannot_exceed_max_depth_kode_anggaran(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->first();
        $leaf = KodeAnggaran::where('tahun_anggaran_id', $ta->id)->where('depth', 3)->first();

        // Level 3 + child = Level 4 (Allowed)
        // Level 4 + child = Level 5 (Exceeds max depth 4)
        $level4 = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'parent_id' => $leaf->id,
            'kode' => $leaf->kode.'.1',
            'nama' => 'Level 4 Node',
            'depth' => 4,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('form_parent_id', $level4->id)
            ->set('form_kode', $level4->kode.'.1')
            ->set('form_nama', 'Level 5 Node')
            ->call('save')
            ->assertHasErrors(['form_parent_id']);
    }

    public function test_can_sahkan_budget_kode_anggaran(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->first();
        $draftNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '9.9',
            'nama' => 'Draft Pos',
            'plafon' => 20000000,
            'budget_status' => 'draft',
            'depth' => 2,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('sahkanBudget', $draftNode->id);

        $this->assertEquals('disahkan', $draftNode->fresh()->budget_status);
    }

    public function test_can_activate_tahun_anggaran(): void
    {
        $year2027 = TahunAnggaran::create([
            'tahun' => 2027,
            'status' => 'draft',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->call('activateYear', $year2027->id);

        $this->assertEquals('aktif', $year2027->fresh()->status);
        $this->assertEquals('draft', TahunAnggaran::where('tahun', 2026)->first()->status);
    }

    public function test_can_add_unit_and_divisi(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(UnitDivisiIndex::class)
            ->call('openCreateModal')
            ->set('form_kode', 'LOG')
            ->set('form_nama', 'Logistik & Aset')
            ->set('form_tipe', 'unit')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('units', [
            'kode' => 'LOG',
            'nama' => 'Logistik & Aset',
            'tipe' => 'unit',
        ]);
    }

    public function test_can_open_detail_modal_in_kode_anggaran(): void
    {
        $node = KodeAnggaran::where('kode', '1')->first();

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->call('openDetailModal', $node->id)
            ->assertSet('showDetailModal', true)
            ->assertSet('detailNodeId', $node->id)
            ->assertSee($node->nama)
            ->assertSee($node->kode)
            ->assertSee('Ringkasan Anggaran Pos')
            ->call('closeDetailModal')
            ->assertSet('showDetailModal', false)
            ->assertSet('detailNodeId', null);
    }

    public function test_can_filter_kode_anggaran_by_prefix(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->first();

        // Create nodes 2.4.1 and 2.4.2
        $node241 = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '2.4.1',
            'nama' => 'Biaya Kepegawaian Khusus',
            'depth' => 2,
            'plafon' => 120000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $node242 = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '2.4.2',
            'nama' => 'Biaya Utilitas Khusus',
            'depth' => 2,
            'plafon' => 30000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('prefixFilter', '2.4.1')
            ->assertSee('Biaya Kepegawaian Khusus')
            ->assertDontSee('Biaya Utilitas Khusus');
    }

    public function test_can_filter_kode_anggaran_by_nominal(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('nominalFilter', 'gt_100m')
            ->assertSee('Beban Operasional & Program') // 500jt > 100jt
            ->assertDontSee('Operasional Kantor & IT'); // 50jt < 100jt
    }
}
