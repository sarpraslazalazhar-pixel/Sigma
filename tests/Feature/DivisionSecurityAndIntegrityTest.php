<?php

namespace Tests\Feature;

use App\Livewire\Master\KodeAnggaranIndex;
use App\Models\KodeAnggaran;
use App\Models\Lampiran;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DivisionSecurityAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_unit_get_divisi_id_returns_correct_id(): void
    {
        $divSek = Unit::where('kode', 'DIV-SEK')->firstOrFail();
        $untSekDir = Unit::where('kode', 'UNT-SEK-DIR')->firstOrFail();

        $this->assertEquals($divSek->id, $divSek->getDivisiId());
        $this->assertEquals($divSek->id, $divSek->divisi_id);
        $this->assertEquals($divSek->id, $untSekDir->getDivisiId());
        $this->assertEquals($divSek->id, $untSekDir->divisi_id);
    }

    public function test_lampiran_access_isolated_by_division(): void
    {
        Storage::fake('local');
        $filePath = 'lampiran/test.pdf';
        Storage::disk('local')->put($filePath, 'fake content');

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();
        $userSek = User::where('username', 'deden.salim')->firstOrFail();
        $userOtherDiv = User::where('username', 'manager.program')->firstOrFail();

        $kode = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '88.1',
            'nama' => 'Testing Node',
            'depth' => 1,
            'plafon' => 5000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $permohonan = Permohonan::create([
            'no_pengajuan' => 'TEST-DIV-001',
            'unit_id' => $userSek->unit_id,
            'user_id' => $userSek->id,
            'kode_anggaran_id' => $kode->id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Tes',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'diajukan',
            'total_diminta' => 1000000,
            'total_disetujui' => 1000000,
        ]);

        $lampiran = Lampiran::create([
            'attachable_type' => Permohonan::class,
            'attachable_id' => $permohonan->id,
            'original_name' => 'test.pdf',
            'path' => $filePath,
            'mime' => 'application/pdf',
            'size' => 12,
            'jenis' => 'bukti',
            'uploaded_by' => $userSek->id,
        ]);

        // 1. User dari divisi berbeda ditolak 403
        $responseCross = $this->actingAs($userOtherDiv)->get(route('lampiran.show', $lampiran->id));
        $responseCross->assertStatus(403);

        // 2. User dari divisi yang sama diizinkan
        $userSameDivisi = User::where('unit_id', Unit::where('kode', 'UNT-SEK-DIR')->first()->id)->firstOrFail();
        $responseSame = $this->actingAs($userSameDivisi)->get(route('lampiran.show', $lampiran->id));
        $responseSame->assertStatus(200);
    }

    public function test_parent_plafon_cannot_be_reduced_below_children_sum(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();

        $parent = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '77',
            'nama' => 'Parent 77',
            'depth' => 1,
            'plafon' => 20000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'parent_id' => $parent->id,
            'kode' => '77.1',
            'nama' => 'Child 77.1',
            'depth' => 2,
            'plafon' => 15000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        // Coba turunkan plafon parent menjadi 10.000.000 (kurang dari anak 15.000.000)
        Livewire::actingAs($admin)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $ta->id)
            ->call('openEditModal', $parent->id)
            ->set('form_plafon', '10000000')
            ->call('save')
            ->assertHasErrors(['form_plafon']);

        $this->assertEquals(20000000, $parent->fresh()->plafon);
    }
}
