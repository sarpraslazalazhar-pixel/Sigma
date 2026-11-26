<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Keuangan\VerifikasiKeuanganIndex;
use App\Livewire\Ump\UmpDetail;
use App\Livewire\Ump\UmpIndex;
use App\Models\Approval;
use App\Models\KodeAnggaran;
use App\Models\Lampiran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\UmpWorkflow;
use App\Services\VerifikasiKeuangan;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class VerifikasiKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $managerKeuanganUser;

    protected User $kepalaKeuanganUser;

    protected User $unitUser;

    protected User $kadivUnitUser;

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

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->managerKeuanganUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->kepalaKeuanganUser = User::where('username', 'direktur.keuangan')->firstOrFail();
        $this->unitUser = User::where('username', 'rudiansah')->firstOrFail();
        $this->kadivUnitUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $this->petugasTu = User::where('username', 'subakti')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
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
            'nama_kegiatan' => 'Kegiatan Verifikasi Laporan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'dicairkan',
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
            'total_penggunaan' => 3000000,
            'saldo' => 2000000,
            'pembulatan' => 2000000,
            'kekurangan' => 0,
            'status' => 'ttd_manager_keuangan',
        ]);

        $item = UmpItem::create([
            'ump_id' => $this->ump->id,
            'tgl' => now()->toDateString(),
            'rincian' => 'Belanja kegiatan verifikasi',
            'no_bukti' => 'NOTA-001',
            'jumlah' => 3000000,
        ]);

        Lampiran::create([
            'attachable_type' => UmpItem::class,
            'attachable_id' => $item->id,
            'jenis' => 'bukti_tf',
            'path' => 'lampiran/ump-item/nota-001.pdf',
            'original_name' => 'nota-001.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'version' => 1,
            'uploaded_by' => $this->unitUser->id,
        ]);
    }

    public function test_verifikasi_page_renders_for_keuangan_tu_and_admin_only(): void
    {
        $this->actingAs($this->managerKeuanganUser)
            ->get(route('keuangan.verifikasi'))
            ->assertOk()
            ->assertSee('Verifikasi Keuangan')
            ->assertSee('PPD-VER-001')
            ->assertSee('Kegiatan Verifikasi Laporan')
            ->assertSee('1 baris · 1 bukti transaksi')
            ->assertSee(route('ump.detail', $this->ump->id))
            ->assertDontSee('PMH-VER-001')
            ->assertDontSee('Jenis dokumen');

        $this->actingAs($this->petugasTu)
            ->get(route('keuangan.verifikasi'))
            ->assertOk()
            ->assertSee('PPD-VER-001');

        $this->actingAs($this->adminUser)
            ->get(route('keuangan.verifikasi'))
            ->assertOk()
            ->assertSee('PPD-VER-001');

        $this->actingAs($this->unitUser)
            ->get(route('keuangan.verifikasi'))
            ->assertForbidden();

        $viewer = User::create([
            'name' => 'Viewer Verifikasi',
            'username' => 'viewer.verifikasi',
            'email' => 'viewer.verifikasi@alazhar.id',
            'password' => 'password',
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $this->actingAs($viewer)
            ->get(route('keuangan.verifikasi'))
            ->assertForbidden();
    }

    public function test_old_cek_ulang_route_and_permohonan_ppd_status_verifikasi_are_removed(): void
    {
        $this->assertFalse(Route::has('keuangan.verifikasi.cek-ulang'));

        $this->actingAs($this->managerKeuanganUser)
            ->get(route('permohonan.detail', $this->permohonan->id))
            ->assertOk()
            ->assertDontSee('Siap Dicek Ulang')
            ->assertDontSee('Verifikasi kelengkapan');

        $this->actingAs($this->managerKeuanganUser)
            ->get(route('pencairan.index', ['ppd' => $this->pencairan->id]))
            ->assertOk()
            ->assertDontSee('Siap Dicek Ulang')
            ->assertDontSee('Verifikasi kelengkapan');
    }

    public function test_only_ump_in_finance_stage_enters_active_queue(): void
    {
        foreach (['draft', 'perlu_perbaikan', 'ttd_pj', 'selesai', 'dibatalkan'] as $nonFinanceStatus) {
            $this->ump->update(['status' => $nonFinanceStatus]);

            Livewire::actingAs($this->managerKeuanganUser)
                ->test(VerifikasiKeuanganIndex::class)
                ->assertDontSee('PPD-VER-001')
                ->set('filterStatus', 'semua')
                ->assertDontSee('PPD-VER-001');
        }

        // Jika pencairan di-void, tidak masuk antrean aktif
        $this->ump->update(['status' => 'ttd_manager_keuangan']);
        $this->pencairan->update(['status' => 'dibatalkan']);
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->assertDontSee('PPD-VER-001');

        // Kembali dicairkan + ttd_kepala_keuangan -> masuk antrean jika belum terverifikasi
        $this->pencairan->update(['status' => 'dicairkan']);
        $this->ump->update(['status' => 'ttd_kepala_keuangan', 'verifikasi_keuangan_at' => null]);
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->assertSee('PPD-VER-001');

        // Riwayat selesai yang terverifikasi tetap terlihat pada tab terverifikasi
        $this->ump->update([
            'status' => 'selesai',
            'verifikasi_keuangan_at' => now(),
            'verifikasi_keuangan_oleh' => $this->petugasTu->id,
        ]);
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->assertDontSee('PPD-VER-001')
            ->set('filterStatus', 'terverifikasi')
            ->assertSee('PPD-VER-001');
    }

    public function test_verifikasi_notifies_manager_keuangan_and_unlocks_approval(): void
    {
        $workflow = new UmpWorkflow;
        $this->assertFalse($workflow->canUserApprove($this->managerKeuanganUser, $this->ump));

        // Dashboard belum menampilkan UMP sebelum lolos Verifikasi Keuangan
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(Dashboard::class)
            ->assertDontSee('PPD-VER-001');

        // UmpIndex & UmpDetail menampilkan status Menunggu Verifikasi Keuangan
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(UmpIndex::class)
            ->assertSee('Menunggu Verifikasi Keuangan');
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->assertSee('Menunggu Verifikasi Keuangan')
            ->assertSee('Buka Verifikasi Keuangan')
            ->assertSee('Verifikasi Keuangan')
            ->assertDontSee('Panel Persetujuan Laporan')
            ->assertViewHas('stepProgress', 2)
            ->assertViewHas('canApprove', false);

        // TU penerbit PPD (subakti) tetap boleh memverifikasi Laporan unit agar tidak deadlock
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->call('pilih', $this->ump->id)
            ->set('catatan', 'Semua nota dan bukti transfer lengkap.')
            ->call('verifikasi', $this->ump->id)
            ->assertHasNoErrors();

        $fresh = $this->ump->fresh();
        $this->assertNotNull($fresh->verifikasi_keuangan_at);
        $this->assertSame($this->petugasTu->id, $fresh->verifikasi_keuangan_oleh);
        $this->assertSame('Semua nota dan bukti transfer lengkap.', $fresh->verifikasi_keuangan_catatan);
        $this->assertSame('ttd_manager_keuangan', $fresh->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'verifikasi_keuangan.verified',
            'entity_type' => Ump::class,
            'entity_id' => $this->ump->id,
        ]);

        // Notifikasi terkirim ke Manager Keuangan, bukan Kepala Divisi Keuangan
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->managerKeuanganUser->id,
            'type' => 'dokumen_menunggu_tindakan',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->kepalaKeuanganUser->id,
            'type' => 'dokumen_menunggu_tindakan',
        ]);

        // Setelah terverifikasi, persetujuan Manager Keuangan terbuka
        $this->assertTrue($workflow->canUserApprove($this->managerKeuanganUser, $fresh));
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(Dashboard::class)
            ->assertSee('PPD-VER-001')
            ->assertSee('Menunggu TTD Mgr Keuangan');
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->assertSee('Menunggu TTD Mgr Keuangan')
            ->assertSee('Terverifikasi oleh '.$this->petugasTu->name)
            ->assertSee('Panel Persetujuan Laporan')
            ->assertViewHas('stepProgress', 3)
            ->assertViewHas('canApprove', true);
    }

    public function test_kembalikan_untuk_perbaikan_returns_ump_to_pj_and_resubmit_requires_reverification(): void
    {
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->call('pilih', $this->ump->id)
            ->set('catatan', 'abc')
            ->call('kembalikan', $this->ump->id)
            ->assertHasErrors(['catatan' => 'min'])
            ->set('catatan', 'Lampirkan kuitansi asli untuk belanja kegiatan.')
            ->call('kembalikan', $this->ump->id)
            ->assertHasNoErrors();

        $returned = $this->ump->fresh();
        $this->assertSame('perlu_perbaikan', $returned->status);
        $this->assertNull($returned->verifikasi_keuangan_at);
        $this->assertSame($this->petugasTu->id, $returned->verifikasi_keuangan_oleh);
        $this->assertSame('Lampirkan kuitansi asli untuk belanja kegiatan.', $returned->verifikasi_keuangan_catatan);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Ump::class,
            'approvable_id' => $this->ump->id,
            'tahap' => 'verifikasi_keuangan',
            'keputusan' => 'perlu_perbaikan',
            'catatan' => 'Lampirkan kuitansi asli untuk belanja kegiatan.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'verifikasi_keuangan.returned',
            'entity_type' => Ump::class,
            'entity_id' => $this->ump->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->unitUser->id,
            'type' => 'dokumen_diputuskan',
        ]);

        // Muncul pada tab Perlu Perbaikan di VerifikasiKeuanganIndex & status-verifikasi di UmpDetail
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'perbaikan')
            ->assertSee('PPD-VER-001')
            ->assertSee('Lampirkan kuitansi asli untuk belanja kegiatan.');

        Livewire::actingAs($this->unitUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->assertSee('Dikembalikan Verifikasi Keuangan untuk Perbaikan')
            ->assertSee('Lampirkan kuitansi asli untuk belanja kegiatan.')
            ->assertSee('Ajukan / Tanda Tangan PJ');

        // PJ memperbarui rincian di Bagian B & ajukan ulang -> kolom verifikasi ter-reset
        $workflow = new UmpWorkflow;
        $workflow->saveItems($returned, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Belanja kegiatan revisi', 'no_bukti' => 'KW-002', 'jumlah' => 3200000],
        ]);
        $workflow->submitByPj($returned->fresh(), $this->unitUser, '');

        $resubmitted = $this->ump->fresh();
        $this->assertSame('ttd_pj', $resubmitted->status);
        $this->assertNull($resubmitted->verifikasi_keuangan_at);
        $this->assertNull($resubmitted->verifikasi_keuangan_oleh);
        $this->assertNull($resubmitted->verifikasi_keuangan_catatan);

        // Diperiksa Oleh (Kadiv) -> masuk kembali ke antrean Verifikasi Keuangan
        $workflow->approve($resubmitted, $this->kadivUnitUser, []);
        $reentered = $this->ump->fresh();
        $this->assertSame('ttd_manager_keuangan', $reentered->status);
        $this->assertNull($reentered->verifikasi_keuangan_at);

        // Tim Keuangan & TU menerima notifikasi masuk antrean Verifikasi Keuangan
        foreach ([$this->petugasTu, $this->managerKeuanganUser] as $verifikator) {
            $notif = $verifikator->notifications()
                ->where('type', 'dokumen_menunggu_tindakan')
                ->latest('created_at')
                ->first();
            $this->assertNotNull($notif);
            $this->assertSame(route('keuangan.verifikasi'), $notif->data['url'] ?? null);
        }

        // Wajib lolos Verifikasi Keuangan lagi sebelum Manager Keuangan bisa approve
        $this->assertFalse($workflow->canUserApprove($this->managerKeuanganUser, $reentered));
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->assertSee('PPD-VER-001');
    }

    public function test_batal_verifikasi_allowed_before_manager_approval_and_rejected_after(): void
    {
        $verification = new VerifikasiKeuangan;
        $verification->decide($this->ump->id, $this->petugasTu, 'verified', 'Awalnya oke.');
        $this->assertNotNull($this->ump->fresh()->verifikasi_keuangan_at);

        // Bisa dibatalkan saat masih ttd_manager_keuangan
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('batalVerifikasi', $this->ump->id)
            ->assertHasNoErrors();

        $unverified = $this->ump->fresh();
        $this->assertNull($unverified->verifikasi_keuangan_at);
        $this->assertNull($unverified->verifikasi_keuangan_oleh);
        $this->assertNull($unverified->verifikasi_keuangan_catatan);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'verifikasi_keuangan.unverified',
            'entity_id' => $this->ump->id,
        ]);

        // Verifikasi ulang lalu disetujui Manager Keuangan -> masuk ttd_kepala_keuangan
        $verification->decide($this->ump->id, $this->petugasTu, 'verified', 'Siap ke Mgr Keu.');
        (new UmpWorkflow)->approve($this->ump->fresh(), $this->managerKeuanganUser, []);
        $this->assertSame('ttd_kepala_keuangan', $this->ump->fresh()->status);

        // Batalkan verifikasi harus ditolak karena sudah disetujui Manager Keuangan
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('batalVerifikasi', $this->ump->id)
            ->assertHasErrors('catatan');

        $this->assertNotNull($this->ump->fresh()->verifikasi_keuangan_at);
    }

    public function test_self_verification_by_pj_is_forbidden_while_ppd_issuer_may_verify(): void
    {
        // Jika PJ adalah petugas TU atau user keuangan sendiri -> dilarang verifikasi diri sendiri (403)
        $this->ump->update(['pj_id' => $this->managerKeuanganUser->id]);

        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->assertSee('Anda adalah PJ Laporan ini (tidak dapat memverifikasi sendiri).')
            ->call('verifikasi', $this->ump->id)
            ->assertForbidden();

        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('catatan', 'Coba kembalikan sendiri.')
            ->call('kembalikan', $this->ump->id)
            ->assertForbidden();

        $this->assertNull($this->ump->fresh()->verifikasi_keuangan_at);

        // TU penerbit PPD (dicairkan_oleh = petugasTu) tetap boleh memverifikasi selama bukan pj_id
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->call('verifikasi', $this->ump->id)
            ->assertHasNoErrors();

        $this->assertNotNull($this->ump->fresh()->verifikasi_keuangan_at);
    }

    public function test_inline_manager_and_kepala_keuangan_approvals_work_end_to_end_from_verifikasi_page(): void
    {
        // 1. Verifikasi oleh TU
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->call('verifikasi', $this->ump->id)
            ->assertHasNoErrors();

        // TU dan Kepala Keuangan tidak berhak memproses approval tahap Manager Keuangan
        Livewire::actingAs($this->petugasTu)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'setuju')
            ->assertForbidden();

        Livewire::actingAs($this->kepalaKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'setuju')
            ->assertForbidden();

        // 2. Manager Keuangan langsung Setujui dari halaman Verifikasi Keuangan
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'setuju')
            ->set('approvalNotes', 'Disetujui Manager Keuangan via halaman verifikasi.')
            ->call('prosesPersetujuan', $this->ump->id)
            ->assertHasNoErrors();

        $this->assertSame('ttd_kepala_keuangan', $this->ump->fresh()->status);

        // Manager Keuangan tidak berhak memproses approval tahap Kepala Divisi Keuangan
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'setuju')
            ->assertForbidden();

        // 3. Kepala Divisi Keuangan langsung Setujui dari halaman Verifikasi Keuangan -> Selesai
        Livewire::actingAs($this->kepalaKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'setuju')
            ->set('approvalNotes', 'Disetujui Kepala Divisi Keuangan.')
            ->call('prosesPersetujuan', $this->ump->id)
            ->assertHasNoErrors();

        $this->assertSame('selesai', $this->ump->fresh()->status);
        $this->assertSame('selesai', $this->permohonan->fresh()->status);

        $this->assertSame(1, Approval::where('approvable_type', Ump::class)
            ->where('approvable_id', $this->ump->id)
            ->where('tahap', 'ttd_manager_keuangan')
            ->where('approver_id', $this->managerKeuanganUser->id)
            ->count());
        $this->assertSame(1, Approval::where('approvable_type', Ump::class)
            ->where('approvable_id', $this->ump->id)
            ->where('tahap', 'ttd_kepala_keuangan')
            ->where('approver_id', $this->kepalaKeuanganUser->id)
            ->count());
    }

    public function test_inline_revision_and_void_from_verifikasi_page(): void
    {
        (new VerifikasiKeuangan)->decide($this->ump->id, $this->petugasTu, 'verified', 'Terverifikasi.');

        // Validasi catatan wajib minimal 5 karakter untuk Minta Perbaikan & Void
        Livewire::actingAs($this->managerKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'perbaikan')
            ->set('approvalNotes', 'abc')
            ->call('prosesPersetujuan', $this->ump->id)
            ->assertHasErrors(['approvalNotes' => 'min'])
            ->set('approvalNotes', 'Nota pembelian belum dilampirkan lengkap.')
            ->call('prosesPersetujuan', $this->ump->id)
            ->assertHasNoErrors();

        $afterRevision = $this->ump->fresh();
        $this->assertSame('perlu_perbaikan', $afterRevision->status);
        $this->assertNull($afterRevision->verifikasi_keuangan_at);
        $this->assertNull($afterRevision->verifikasi_keuangan_oleh);
        $this->assertNull($afterRevision->verifikasi_keuangan_catatan);
        $this->assertFalse((new VerifikasiKeuangan)->returned()->whereKey($this->ump->id)->exists());

        // Kembalikan ke status siap persetujuan untuk menguji Void langsung oleh Kepala Keuangan
        $afterRevision->forceFill([
            'status' => 'ttd_kepala_keuangan',
            'verifikasi_keuangan_at' => now(),
            'verifikasi_keuangan_oleh' => $this->petugasTu->id,
        ])->save();

        Livewire::actingAs($this->kepalaKeuanganUser)
            ->test(VerifikasiKeuanganIndex::class)
            ->set('filterStatus', 'menunggu_persetujuan')
            ->call('bukaPersetujuan', $this->ump->id, 'batal')
            ->set('approvalNotes', 'Kegiatan dibatalkan resmi oleh pimpinan.')
            ->call('prosesPersetujuan', $this->ump->id)
            ->assertHasNoErrors();

        $this->assertSame('dibatalkan', $this->ump->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ump.cancelled',
            'entity_id' => $this->ump->id,
        ]);
    }

    public function test_gate_respects_blocking_verification_for_admin_and_allows_tu_cross_division_view(): void
    {
        // Admin tidak boleh melewati blocking gate via Gate::allows('approve', $ump)
        $this->assertFalse(Gate::forUser($this->adminUser)->allows('approve', $this->ump));

        (new VerifikasiKeuangan)->decide($this->ump->id, $this->petugasTu, 'verified', 'Lengkap.');
        $this->assertTrue(Gate::forUser($this->adminUser)->allows('approve', $this->ump->fresh()));

        // Petugas TU lintas divisi tetap dapat membuka detail Laporan dari Verifikasi Keuangan
        $otherDivUnit = Unit::where('kode', 'DIV-PRG')->firstOrFail();
        $this->permohonan->update(['unit_id' => $otherDivUnit->id]);
        $this->assertTrue(Gate::forUser($this->petugasTu)->allows('view', $this->ump->fresh()));
    }
}
