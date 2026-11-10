<?php

namespace Tests\Feature;

use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Livewire\Permohonan\PermohonanEdit;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermohonanAuditAndEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected User $managerUser;

    protected User $supervisorUser;

    protected User $kadivUser;

    protected TahunAnggaran $ta;

    protected Unit $unitKlm;

    protected KodeAnggaran $leafNode;

    protected SumberDana $sumberDanaInfak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->staffUser = User::where('username', 'subakti')->firstOrFail(); // TU
        $this->managerUser = User::where('username', 'deden.salim')->firstOrFail();
        $this->supervisorUser = User::where('username', 'anas.priyogi')->firstOrFail();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();

        $this->ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $this->sumberDanaInfak = SumberDana::where('nama', 'like', '%Infak Umum%')->firstOrFail();

        $root = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8',
            'nama' => 'Root Anggaran',
            'depth' => 1,
            'plafon' => 100000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $root->id,
            'kode' => '8.1',
            'nama' => 'Pos Anggaran Operasional',
            'depth' => 2,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    public function test_create_stores_pengusul_id(): void
    {
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('user_id', $this->managerUser->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('nama_kegiatan', 'Audit Trail Test Activity')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('items.0.uraian', 'Kebutuhan ATK Kantor')
            ->set('items.0.jumlah_diminta', '2000000')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permohonan', [
            'nama_kegiatan' => 'Audit Trail Test Activity',
            'user_id' => $this->managerUser->id,
            'pengusul_id' => $this->staffUser->id,
            'status' => 'draft',
        ]);
    }

    public function test_detail_displays_pengusul_name_when_different_from_pemohon(): void
    {
        $pmh = Permohonan::create([
            'no_pengajuan' => 'DRAFT-AUDIT-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'pengusul_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Anggaran Operasional',
            'nama_kegiatan' => 'Kegiatan Audit Pengusul',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 1000000,
        ]);

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->assertSee('Diusulkan oleh')
            ->assertSee($this->staffUser->name);
    }

    public function test_change_pemohon_modal_in_detail_updates_user_and_unit(): void
    {
        $pmh = Permohonan::create([
            'no_pengajuan' => 'DRAFT-CHANGE-001',
            'unit_id' => $this->supervisorUser->unit_id,
            'user_id' => $this->supervisorUser->id,
            'pengusul_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Anggaran Operasional',
            'nama_kegiatan' => 'Kegiatan Ganti Pemohon',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 1500000,
        ]);

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->assertSee('Alih Pejabat Pemohon')
            ->call('openChangePemohonModal')
            ->assertSet('showChangePemohonModal', true)
            ->set('newPemohonId', $this->managerUser->id)
            ->call('updatePejabatPemohon')
            ->assertHasNoErrors()
            ->assertSet('showChangePemohonModal', false);

        $pmh->refresh();
        $this->assertEquals($this->managerUser->id, $pmh->user_id);
        $this->assertEquals($this->managerUser->unit_id, $pmh->unit_id);
    }

    public function test_change_pemohon_modal_blocks_if_nominal_exceeds_new_pemohon_limit(): void
    {
        $pmh = Permohonan::create([
            'no_pengajuan' => 'DRAFT-CHANGE-002',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'pengusul_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Anggaran Operasional',
            'nama_kegiatan' => 'Kegiatan Over Limit Ganti',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 5000000, // > 2.000.000 batas supervisor
        ]);

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->call('openChangePemohonModal')
            ->set('newPemohonId', $this->supervisorUser->id)
            ->call('updatePejabatPemohon')
            ->assertHasErrors(['newPemohonId'])
            ->assertSee('melebihi batas kewenangan');

        $pmh->refresh();
        $this->assertEquals($this->kadivUser->id, $pmh->user_id);
    }

    public function test_edit_permohonan_page_allows_updating_draft_items_and_fields(): void
    {
        $pmh = Permohonan::create([
            'no_pengajuan' => 'DRAFT-EDIT-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'pengusul_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Anggaran Operasional',
            'nama_kegiatan' => 'Kegiatan Sebelum Diedit',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 2000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $pmh->id,
            'uraian' => 'Item Pertama',
            'jumlah_diminta' => 2000000,
        ]);

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanEdit::class, ['id' => $pmh->id])
            ->assertSet('nama_kegiatan', 'Kegiatan Sebelum Diedit')
            ->set('nama_kegiatan', 'Kegiatan Sesudah Diedit Sukses')
            ->set('items.0.uraian', 'Item Pertama Diperbarui')
            ->set('items.0.jumlah_diminta', '3500000')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $pmh->refresh();
        $this->assertEquals('Kegiatan Sesudah Diedit Sukses', $pmh->nama_kegiatan);
        $this->assertEquals(3500000, $pmh->total_diminta);
        $this->assertDatabaseHas('permohonan_items', [
            'permohonan_id' => $pmh->id,
            'uraian' => 'Item Pertama Diperbarui',
            'jumlah_diminta' => 3500000,
        ]);
    }
}
