<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\VerifikasiKeuanganIndex;
use App\Models\KodeAnggaran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VerifikasiKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $keuanganUser;

    protected User $unitUser;

    protected Permohonan $permohonan;

    protected Pencairan $pencairan;

    protected Ump $ump;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('role', 'admin')->firstOrFail();
        $this->keuanganUser = User::where('role', 'keuangan')->firstOrFail();
        $this->unitUser = User::where('role', 'unit')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '99.1',
            'nama' => 'Pos Verifikasi Keuangan',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'PMH-VER-001',
            'unit_id' => $unit->id,
            'user_id' => $this->unitUser->id,
            'kode_anggaran_id' => $node->id,
            'program' => 'Program Verifikasi',
            'nama_kegiatan' => 'Kegiatan Verifikasi',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
            'perlu_verifikasi_keuangan' => true,
        ]);

        $this->pencairan = Pencairan::create([
            'permohonan_id' => $this->permohonan->id,
            'no_ppd' => 'PPD-VER-001',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 5000000,
            'status' => 'dicairkan',
            'perlu_verifikasi_keuangan' => true,
        ]);

        $this->ump = Ump::create([
            'pencairan_id' => $this->pencairan->id,
            'tgl_terima' => now()->toDateString(),
            'tujuan' => 'UMP Butuh Verifikasi',
            'pj_id' => $this->unitUser->id,
            'jumlah_total' => 5000000,
            'status' => 'selesai',
            'perlu_verifikasi_keuangan' => true,
        ]);
    }

    public function test_verifikasi_page_renders_for_keuangan_role(): void
    {
        $this->actingAs($this->keuanganUser)
            ->get(route('keuangan.verifikasi'))
            ->assertStatus(200)
            ->assertSee('Verifikasi Keuangan (Mode Atas Nama)')
            ->assertSee('PMH-VER-001')
            ->assertSee('PPD-VER-001');
    }

    public function test_verifikasi_page_renders_for_admin_role(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('keuangan.verifikasi'))
            ->assertStatus(200);
    }

    public function test_verifikasi_page_forbidden_for_unit_role(): void
    {
        $this->actingAs($this->unitUser)
            ->get(route('keuangan.verifikasi'))
            ->assertStatus(403);
    }

    public function test_verifikasi_marks_document_verified(): void
    {
        $this->actingAs($this->keuanganUser);

        Livewire::test(VerifikasiKeuanganIndex::class)
            ->call('verifikasi', 'permohonan', $this->permohonan->id)
            ->assertHasNoErrors();

        $fresh = $this->permohonan->fresh();
        $this->assertNotNull($fresh->verifikasi_keuangan_at);
        $this->assertEquals($this->keuanganUser->id, $fresh->verifikasi_keuangan_oleh);
    }

    public function test_batal_verifikasi_resets_verification_status(): void
    {
        $this->permohonan->update([
            'verifikasi_keuangan_at' => now(),
            'verifikasi_keuangan_oleh' => $this->keuanganUser->id,
        ]);

        $this->actingAs($this->keuanganUser);

        Livewire::test(VerifikasiKeuanganIndex::class)
            ->call('batalVerifikasi', 'permohonan', $this->permohonan->id)
            ->assertHasNoErrors();

        $fresh = $this->permohonan->fresh();
        $this->assertNull($fresh->verifikasi_keuangan_at);
        $this->assertNull($fresh->verifikasi_keuangan_oleh);
    }

    public function test_filter_jenis_and_status(): void
    {
        $this->actingAs($this->keuanganUser);

        Livewire::test(VerifikasiKeuanganIndex::class)
            ->set('filterJenis', 'pencairan')
            ->assertSee('PPD-VER-001')
            ->assertDontSee('PMH-VER-001');
    }
}
