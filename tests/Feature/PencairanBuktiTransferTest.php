<?php

namespace Tests\Feature;

use App\Livewire\Pencairan\PencairanIndex;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Livewire\Ump\UmpDetail;
use App\Models\AuditLog;
use App\Models\KodeAnggaran;
use App\Models\Lampiran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpPenerima;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PencairanBuktiTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;

    protected User $adminUser;

    protected User $petugasUser;

    protected User $plainUser;

    protected User $sameDivisionSiblingUser;

    protected User $otherDivisionUser;

    protected User $managerUser;

    protected Permohonan $permohonan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->adminUser = User::where('role', 'admin')->firstOrFail();
        $this->petugasUser = User::where('username', 'subakti')->firstOrFail();
        $this->plainUser = User::where('username', 'rudiansah')->firstOrFail();
        $this->sameDivisionSiblingUser = User::where('username', 'sigit.nugroho')->firstOrFail();
        $this->otherDivisionUser = User::where('username', 'manager.program')->firstOrFail();
        $this->managerUser = User::where('username', 'deden.salim')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '97.1',
            'nama' => 'Tes Bukti Transfer PPD',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9701',
            'unit_id' => $unit->id,
            'user_id' => $this->plainUser->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Penyaluran Bantuan Pendidikan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 4500000,
            'total_disetujui' => 4500000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $this->permohonan->id,
            'uraian' => 'Bantuan biaya sekolah',
            'jumlah_diminta' => 4500000,
            'jumlah_disetujui' => 4500000,
        ]);
    }

    public function test_cairkan_with_bukti_transfer_via_livewire_stores_private_lampiran(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $file = UploadedFile::fake()->create('bukti-tf-ppd.pdf', 256, 'application/pdf');

        Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $this->permohonan->id])
            ->call('openCairkanModal')
            ->set('noPpd', 'PPD/202611/9001')
            ->set('tglCair', '2026-11-24')
            ->set('pjId', $this->managerUser->id)
            ->set('buktiTransferFile', $file)
            ->call('submitCairkan')
            ->assertHasNoErrors();

        $pencairan = Pencairan::where('no_ppd', 'PPD/202611/9001')->with('buktiTransfer')->firstOrFail();

        $lampiran = $pencairan->buktiTransfer;
        $this->assertNotNull($lampiran);
        $this->assertSame(Pencairan::class, $lampiran->attachable_type);
        $this->assertSame($pencairan->id, $lampiran->attachable_id);
        $this->assertSame('bukti_transfer', $lampiran->jenis);
        $this->assertSame('bukti-tf-ppd.pdf', $lampiran->original_name);
        $this->assertSame(1, $lampiran->version);
        $this->assertSame($this->financeUser->id, $lampiran->uploaded_by);
        $this->assertNotEmpty($lampiran->sha256);
        $this->assertStringStartsWith('lampiran/pencairan/bukti_transfer/', $lampiran->path);

        Storage::disk('local')->assertExists($lampiran->path);
        Storage::disk('public')->assertMissing($lampiran->path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pencairan.bukti_transfer_uploaded',
            'entity_type' => Pencairan::class,
            'entity_id' => $pencairan->id,
        ]);
    }

    public function test_cairkan_without_bukti_transfer_then_upload_and_replace_increments_version(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $this->permohonan->id])
            ->call('openCairkanModal')
            ->set('noPpd', 'PPD/202611/9002')
            ->set('tglCair', '2026-11-24')
            ->set('pjId', $this->managerUser->id)
            ->call('submitCairkan')
            ->assertHasNoErrors();

        $pencairan = Pencairan::where('no_ppd', 'PPD/202611/9002')->firstOrFail();
        $this->assertNull($pencairan->buktiTransfer);

        // Unggah susulan lewat halaman PencairanIndex (versi 1)
        $firstFile = UploadedFile::fake()->image('slip-transfer-v1.jpg');

        Livewire::actingAs($this->financeUser)
            ->test(PencairanIndex::class)
            ->assertSee('Belum Ada Bukti TF')
            ->call('bukaModalBuktiTransfer', $pencairan->id)
            ->set('buktiTransferFile', $firstFile)
            ->call('simpanBuktiTransfer')
            ->assertHasNoErrors()
            ->assertSee('Lihat Bukti TF')
            ->assertSee('Ganti TF');

        $v1 = $pencairan->fresh()->buktiTransfer;
        $this->assertNotNull($v1);
        $this->assertSame(1, $v1->version);
        $this->assertSame('slip-transfer-v1.jpg', $v1->original_name);
        Storage::disk('local')->assertExists($v1->path);

        // Perbarui lewat halaman PermohonanDetail (versi 2)
        $secondFile = UploadedFile::fake()->create('slip-transfer-v2.pdf', 300, 'application/pdf');

        Livewire::actingAs($this->petugasUser)
            ->test(PermohonanDetail::class, ['id' => $this->permohonan->id])
            ->assertSee('Lihat Bukti Transfer')
            ->call('openBuktiTransferModal')
            ->set('buktiTransferUpload', $secondFile)
            ->call('submitBuktiTransfer')
            ->assertHasNoErrors();

        $pencairan->refresh();
        $latest = $pencairan->buktiTransfer;
        $this->assertNotNull($latest);
        $this->assertSame(2, $latest->version);
        $this->assertSame('slip-transfer-v2.pdf', $latest->original_name);
        $this->assertSame($this->petugasUser->id, $latest->uploaded_by);
        Storage::disk('local')->assertExists($latest->path);

        // Pastikan versi lama masih tersimpan untuk riwayat audit dan latestOfMany memilih versi 2
        $this->assertSame(2, $pencairan->lampiran()->where('jenis', 'bukti_transfer')->count());
        $this->assertSame(2, AuditLog::where('action', 'pencairan.bukti_transfer_uploaded')
            ->where('entity_id', $pencairan->id)
            ->count());

        // Jika ada lampiran verifikasi keuangan ('bukti') dengan ID lebih besar, buktiTransfer tetap mengembalikan bukti_transfer
        $pencairan->lampiran()->create([
            'jenis' => 'bukti',
            'path' => 'lampiran/verifikasi-keuangan/koreksi.pdf',
            'original_name' => 'koreksi.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'version' => 1,
            'uploaded_by' => $this->petugasUser->id,
        ]);

        $this->assertSame($latest->id, $pencairan->fresh()->buktiTransfer->id);

        // UmpDetail menampilkan tautan baca-saja Lihat Bukti Transfer PPD
        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        Livewire::actingAs($this->plainUser)
            ->test(UmpDetail::class, ['id' => $ump->id])
            ->assertSee('Lihat Bukti Transfer PPD')
            ->assertSee(route('lampiran.show', $latest->id));
    }

    public function test_plain_unit_user_and_cancelled_ppd_cannot_upload_bukti_transfer(): void
    {
        Storage::fake('local');

        $workflow = new PermohonanWorkflow;
        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/202611/9003',
            'tgl_cair' => '2026-11-24',
        ]);

        $file = UploadedFile::fake()->create('ilegal.pdf', 120, 'application/pdf');

        // Livewire PencairanIndex ditolak 403 untuk role unit biasa
        Livewire::actingAs($this->plainUser)
            ->test(PencairanIndex::class)
            ->call('bukaModalBuktiTransfer', $pencairan->id)
            ->assertForbidden();

        // Livewire PermohonanDetail ditolak 403 untuk role unit biasa
        Livewire::actingAs($this->plainUser)
            ->test(PermohonanDetail::class, ['id' => $this->permohonan->id])
            ->call('openBuktiTransferModal')
            ->assertForbidden();

        // Service layer juga menolak role unit biasa
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak berwenang');
        $workflow->simpanBuktiTransfer($pencairan, $this->plainUser, $file);
    }

    public function test_uploading_bukti_transfer_to_cancelled_ppd_is_rejected(): void
    {
        Storage::fake('local');

        $workflow = new PermohonanWorkflow;
        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/202611/9004',
            'tgl_cair' => '2026-11-24',
        ]);

        $workflow->batalkanPencairan($pencairan, $this->financeUser, 'Pembatalan uji coba');

        $file = UploadedFile::fake()->create('batal.pdf', 120, 'application/pdf');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Void');
        $workflow->simpanBuktiTransfer($pencairan->fresh(), $this->financeUser, $file);
    }

    public function test_lampiran_show_authorization_for_pencairan_bukti_transfer(): void
    {
        Storage::fake('local');

        $workflow = new PermohonanWorkflow;
        $file = UploadedFile::fake()->create('bukti-otorisasi.pdf', 150, 'application/pdf');

        // Gunakan PJ dari divisi berbeda untuk memastikan PJ lintas divisi juga diizinkan
        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/202611/9005',
            'tgl_cair' => '2026-11-24',
            'pj_id' => $this->otherDivisionUser->id,
            'bukti_transfer' => $file,
        ]);

        $lampiran = $pencairan->buktiTransfer;
        $this->assertNotNull($lampiran);

        // Diizinkan: Keuangan, Admin, Petugas Input, Pemohon, Pengguna satu divisi, PJ UMP
        $this->actingAs($this->financeUser)->get(route('lampiran.show', $lampiran->id))->assertOk();
        $this->actingAs($this->adminUser)->get(route('lampiran.show', $lampiran->id))->assertOk();
        $this->actingAs($this->petugasUser)->get(route('lampiran.show', $lampiran->id))->assertOk();
        $this->actingAs($this->plainUser)->get(route('lampiran.show', $lampiran->id))->assertOk();
        $this->actingAs($this->sameDivisionSiblingUser)->get(route('lampiran.show', $lampiran->id))->assertOk();
        $this->actingAs($this->otherDivisionUser)->get(route('lampiran.show', $lampiran->id))->assertOk();

        // Buat pengguna divisi lain yang bukan PJ, penerima, pengusul, admin, keuangan, maupun TU
        $unrelatedOtherDivUser = User::create([
            'name' => 'Staf Divisi Lain',
            'username' => 'staf.divisi.lain',
            'email' => 'staf.lain@lazalazhar.org',
            'password' => 'password',
            'role' => 'unit',
            'unit_id' => $this->otherDivisionUser->unit_id,
            'is_petugas_input' => false,
            'is_active' => true,
        ]);

        // Ditolak 403 untuk pengguna divisi lain yang tidak terkait
        $this->actingAs($unrelatedOtherDivUser)->get(route('lampiran.show', $lampiran->id))->assertForbidden();

        // Jika ditunjuk sebagai penerima UMP, pengguna tersebut menjadi berwenang
        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        UmpPenerima::create([
            'ump_id' => $ump->id,
            'user_id' => $unrelatedOtherDivUser->id,
        ]);

        $this->actingAs($unrelatedOtherDivUser)->get(route('lampiran.show', $lampiran->id))->assertOk();
    }

    public function test_invalid_mime_extension_is_rejected_with_indonesian_message(): void
    {
        Storage::fake('local');

        $invalidFile = UploadedFile::fake()->create('catatan.txt', 50, 'text/plain');

        // 1. Pada modal Terbitkan PPD di PermohonanDetail
        Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $this->permohonan->id])
            ->call('openCairkanModal')
            ->set('noPpd', 'PPD/202611/9006')
            ->set('tglCair', '2026-11-24')
            ->set('pjId', $this->managerUser->id)
            ->set('buktiTransferFile', $invalidFile)
            ->call('submitCairkan')
            ->assertHasErrors(['buktiTransferFile' => 'mimes']);

        // 2. Pada modal unggah susulan di PencairanIndex
        $pencairan = (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/202611/9007',
            'tgl_cair' => '2026-11-24',
        ]);

        Livewire::actingAs($this->financeUser)
            ->test(PencairanIndex::class)
            ->call('bukaModalBuktiTransfer', $pencairan->id)
            ->set('buktiTransferFile', $invalidFile)
            ->call('simpanBuktiTransfer')
            ->assertHasErrors(['buktiTransferFile' => 'mimes']);

        $this->assertNull($pencairan->fresh()->buktiTransfer);
    }
}
