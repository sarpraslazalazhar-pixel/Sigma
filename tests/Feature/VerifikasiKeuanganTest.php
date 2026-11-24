<?php

namespace Tests\Feature;

use App\Livewire\Keuangan\VerifikasiKeuanganIndex;
use App\Models\AuditLog;
use App\Models\KodeAnggaran;
use App\Models\Leveling;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VerifikasiKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $keuanganUser;

    protected User $unitUser;

    protected User $petugasTu;

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
        $this->unitUser = User::where('role', 'unit')->where('is_petugas_input', false)->firstOrFail();
        $this->petugasTu = User::where('role', 'unit')->where('is_petugas_input', true)->firstOrFail();

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
            'pengusul_id' => $this->petugasTu->id,
            'kode_anggaran_id' => $node->id,
            'program' => 'Program Verifikasi',
            'nama_kegiatan' => 'Kegiatan Verifikasi',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);

        $this->pencairan = Pencairan::create([
            'permohonan_id' => $this->permohonan->id,
            'no_ppd' => 'PPD-VER-001',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 5000000,
            'status' => 'dicairkan',
            'dicairkan_oleh' => $this->petugasTu->id,
        ]);

        $this->ump = Ump::create([
            'pencairan_id' => $this->pencairan->id,
            'tgl_terima' => now()->toDateString(),
            'tujuan' => 'Laporan Butuh Verifikasi',
            'pj_id' => $this->unitUser->id,
            'jumlah_total' => 5000000,
            'status' => 'ttd_pj',
        ]);
    }

    public function test_verifikasi_page_renders_for_keuangan_role(): void
    {
        $this->actingAs($this->keuanganUser)
            ->get(route('keuangan.verifikasi'))
            ->assertStatus(200)
            ->assertSee('Verifikasi Keuangan')
            ->assertSee('PMH-VER-001')
            ->assertSee(route('pencairan.index', ['ppd' => $this->pencairan->id]))
            ->assertSee(route('ump.detail', $this->ump->id));
    }

    public function test_verifikasi_page_renders_for_admin_role(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('keuangan.verifikasi'))
            ->assertStatus(200);
    }

    public function test_antrean_pusat_terlarang_untuk_petugas_input(): void
    {
        $this->actingAs($this->petugasTu)
            ->get(route('keuangan.verifikasi'))
            ->assertForbidden();
    }

    public function test_detail_ppd_menolak_id_tidak_valid(): void
    {
        $this->actingAs($this->keuanganUser)
            ->get(route('pencairan.index', ['ppd' => 'bukan-nomor']))
            ->assertNotFound();
    }

    public function test_permohonan_menunggu_hanya_masuk_setelah_sampai_tahap_keuangan(): void
    {
        $this->permohonan->update([
            'status' => 'diketahui_kadiv',
            'leveling_required_id' => Leveling::where('urutan', 1)->sole()->id,
        ]);
        $page = Livewire::actingAs($this->keuanganUser)->test(VerifikasiKeuanganIndex::class)
            ->set('filterJenis', 'permohonan')->assertDontSee('PMH-VER-001');
        $page->call('verifikasi', 'permohonan', $this->permohonan->id)->assertHasErrors('catatan');

        $this->permohonan->update(['leveling_required_id' => Leveling::where('urutan', 2)->sole()->id]);
        Livewire::actingAs($this->keuanganUser)->test(VerifikasiKeuanganIndex::class)
            ->set('filterJenis', 'permohonan')->assertSee('PMH-VER-001');
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
            ->set('catatan', 'Semua bukti tersedia.')
            ->call('verifikasi', 'permohonan', $this->permohonan->id)
            ->assertHasNoErrors();

        $fresh = $this->permohonan->fresh();
        $this->assertNotNull($fresh->verifikasi_keuangan_at);
        $this->assertEquals($this->keuanganUser->id, $fresh->verifikasi_keuangan_oleh);
        $this->assertSame('Semua bukti tersedia.', $fresh->verifikasi_keuangan_catatan);
        $this->assertSame('disetujui_keuangan', $fresh->status);
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

    public function test_ppd_dikoreksi_dan_diajukan_ulang_oleh_penerbit_tanpa_membatalkan_pencairan(): void
    {
        Storage::fake('local');
        $route = route('keuangan.verifikasi.cek-ulang', ['type' => 'pencairan', 'id' => $this->pencairan->id]);
        $source = route('pencairan.index', ['ppd' => $this->pencairan->id]);

        Livewire::actingAs($this->keuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterJenis', 'pencairan')
            ->call('kembalikan', 'pencairan', $this->pencairan->id)
            ->assertHasErrors('catatan')
            ->set('catatan', 'Lampirkan bukti transfer yang jelas.')
            ->call('kembalikan', 'pencairan', $this->pencairan->id)
            ->assertHasNoErrors()
            ->assertDontSee('PPD-VER-001')
            ->set('filterStatus', 'perbaikan')
            ->assertSee('Lampirkan bukti transfer yang jelas.');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->petugasTu->id,
            'type' => 'dokumen_diputuskan',
        ]);
        $this->actingAs($this->unitUser)->post($route)->assertForbidden();
        $this->actingAs($this->petugasTu)->get($source)
            ->assertOk()->assertSee('Lampirkan bukti transfer yang jelas.')->assertSee('Siap Dicek Ulang');
        $this->from($source)->post($route, [
            'bukti_koreksi' => UploadedFile::fake()->create('bukti.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('bukti_koreksi');
        $this->post($route, [
            'bukti_koreksi' => UploadedFile::fake()->create('bukti.pdf', 1, 'application/pdf'),
        ])->assertRedirect($source);

        $payout = $this->pencairan->fresh();
        $this->assertSame('dicairkan', $payout->status);
        $this->assertSame('PPD-VER-001', $payout->no_ppd);
        $this->assertSame('ttd_pj', $this->ump->fresh()->status);
        Storage::disk('local')->assertExists($payout->lampiran()->sole()->path);
        $returned = AuditLog::where('action', 'verifikasi_keuangan.returned')->sole();
        $this->assertSame('Lampirkan bukti transfer yang jelas.', $returned->after['verifikasi_keuangan_catatan']);

        Livewire::actingAs($this->keuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterJenis', 'pencairan')
            ->assertSee('PPD-VER-001');
    }

    public function test_admin_tidak_bisa_memutus_dokumen_yang_dia_input_atau_terbitkan(): void
    {
        $this->pencairan->update(['dicairkan_oleh' => $this->adminUser->id]);
        $this->permohonan->update(['pengusul_id' => $this->adminUser->id]);

        Livewire::actingAs($this->adminUser)->test(VerifikasiKeuanganIndex::class)
            ->call('verifikasi', 'pencairan', $this->pencairan->id)->assertStatus(403);
        Livewire::actingAs($this->adminUser)->test(VerifikasiKeuanganIndex::class)
            ->set('catatan', 'Koreksi dokumen sendiri.')
            ->call('kembalikan', 'permohonan', $this->permohonan->id)->assertStatus(403);
        $this->assertNull($this->pencairan->fresh()->verifikasi_keuangan_at);
        $this->assertNull($this->permohonan->fresh()->verifikasi_keuangan_catatan);
    }

    public function test_draft_dan_dokumen_selesai_tidak_masuk_antrean_tapi_riwayat_tetap_tersedia(): void
    {
        $this->ump->update(['status' => 'draft']);
        $page = Livewire::actingAs($this->keuanganUser)->test(VerifikasiKeuanganIndex::class)
            ->set('filterJenis', 'ump')->assertDontSee('PPD-VER-001');
        $page->call('verifikasi', 'ump', $this->ump->id)->assertHasErrors('catatan');

        $this->permohonan->update(['status' => 'selesai']);
        $this->ump->update([
            'status' => 'selesai',
            'verifikasi_keuangan_at' => now(),
            'verifikasi_keuangan_oleh' => $this->keuanganUser->id,
        ]);
        Livewire::actingAs($this->keuanganUser)->test(VerifikasiKeuanganIndex::class)
            ->assertDontSee('PMH-VER-001')->assertDontSee('PPD-VER-001')
            ->set('filterStatus', 'terverifikasi')->assertSee(route('ump.detail', $this->ump->id));
    }
}
