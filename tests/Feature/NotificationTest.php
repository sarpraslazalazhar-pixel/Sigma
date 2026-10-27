<?php

namespace Tests\Feature;

use App\Livewire\Components\NotificationBell;
use App\Mail\ReminderNotificationMail;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\Setting;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PermohonanWorkflow;
use App\Services\UmpWorkflow;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected User $managerUser;

    protected User $kadivUser;

    protected User $financeUser;

    protected User $adminUser;

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
        $this->adminUser = User::where('username', 'admin')->first();

        $this->ta = TahunAnggaran::where('status', 'aktif')->first();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->first();
        $this->sumberDana = SumberDana::first();

        $root = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '2',
            'nama' => 'Penyaluran',
            'depth' => 1,
            'plafon' => 10000000, // 10 juta
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $root->id,
            'kode' => '2.1',
            'nama' => 'Penyaluran Reguler',
            'depth' => 2,
            'plafon' => 10000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    private function createPermohonanDraft(int $nominal = 5000000): Permohonan
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'atasan_id' => $this->managerUser->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'nama_kegiatan' => 'Kegiatan Notif Test',
            'latar_belakang' => 'Testing Notifikasi In-App',
            'total_diminta' => $nominal,
            'status' => 'draft',
            'tgl_mulai' => now()->toDateString(),
            'tgl_selesai' => now()->addDays(2)->toDateString(),
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item 1',
            'volume' => 1,
            'satuan' => 'Paket',
            'harga_satuan' => $nominal,
            'jumlah_diminta' => $nominal,
        ]);

        return $permohonan;
    }

    public function test_submit_permohonan_notifies_designated_atasan(): void
    {
        $permohonan = $this->createPermohonanDraft();
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);

        // Tahap 2: pemeriksa tetap adalah Kepala Divisi Sekretariat
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->kadivUser->id,
            'type' => 'dokumen_menunggu_tindakan',
        ]);

        $notif = DB::table('notifications')
            ->where('notifiable_id', $this->kadivUser->id)
            ->where('type', 'dokumen_menunggu_tindakan')
            ->first();

        $this->assertNotNull($notif);
        $this->assertStringContainsString("tunggu_permohonan_{$permohonan->id}_diajukan", $notif->dedup_key);
        $data = json_decode($notif->data, true);
        $this->assertEquals('permohonan', $data['jenis']);
        $this->assertEquals($permohonan->id, $data['entity_id']);
    }

    public function test_notification_deduplication_is_idempotent(): void
    {
        $permohonan = $this->createPermohonanDraft();
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);

        $initialCount = DB::table('notifications')->count();

        // Coba kirim lagi dengan dedup_key yang sama
        $siklus = 1;
        NotificationService::kirim(
            $this->kadivUser,
            'dokumen_menunggu_tindakan',
            ['test' => 1],
            "tunggu_permohonan_{$permohonan->id}_diajukan_{$siklus}"
        );

        $this->assertEquals($initialCount, DB::table('notifications')->count());
    }

    public function test_permohonan_full_approval_chain_sends_notifications(): void
    {
        $permohonan = $this->createPermohonanDraft(8500000); // 85% dari 10jt -> memicu Limit!
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);

        // Tahap 2: Diperiksa oleh Kadiv Sekretariat
        $workflow->approve($permohonan, $this->kadivUser);

        // Pengaju dapat notif putusan
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->staffUser->id,
            'type' => 'dokumen_diputuskan',
        ]);

        // Keuangan dapat notif tunggu tindakan
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->financeUser->id,
            'type' => 'dokumen_menunggu_tindakan',
        ]);

        // Tahap 3: Approve oleh keuangan -> status disetujui_keuangan
        // Karena nominal 8.500.000 (85% dari plafon 10.000.000), ini melewati batas ambang.limit (80%)!
        $workflow->approve($permohonan, $this->financeUser, [
            'items' => [$permohonan->items->first()->id => 8500000],
        ]);

        // Verifikasi notifikasi perubahan status anggaran (Limit) terkirim ke admin/keuangan
        $this->assertDatabaseHas('notifications', [
            'type' => 'anggaran_berubah_status',
        ]);

        // Cairkan
        $pencairan = $workflow->cairkan($permohonan, $this->financeUser, [
            'no_ppd' => 'PPD-2026-999',
            'tgl_cair' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->staffUser->id,
            'type' => 'dokumen_diputuskan',
            'dedup_key' => "putusan_permohonan_{$permohonan->id}_dicairkan_{$pencairan->id}",
        ]);
    }

    public function test_ump_workflow_and_remind_notifications(): void
    {
        Setting::set('ump.tenggat_hari', 14);

        $permohonan = $this->createPermohonanDraft(2000000);
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);
        $workflow->approve($permohonan, $this->kadivUser);
        $workflow->approve($permohonan, $this->financeUser);
        $pencairan = $workflow->cairkan($permohonan, $this->financeUser, [
            'no_ppd' => 'PPD-2026-777',
            'tgl_cair' => now()->toDateString(),
        ]);

        $ump = $pencairan->ump;
        $umpWorkflow = new UmpWorkflow;

        // Simpan baris item
        $umpWorkflow->saveItems($ump, [
            [
                'tgl' => now()->toDateString(),
                'rincian' => 'Beli ATK',
                'no_bukti' => 'BKT-01',
                'jumlah' => 2000000,
            ],
        ]);

        // Submit oleh PJ
        $umpWorkflow->submitByPj($ump, $this->staffUser, 'Catatan submit');

        // Kadiv menerima notifikasi UMP tunggu tindakan
        $this->assertDatabaseHas('notifications', [
            'type' => 'dokumen_menunggu_tindakan',
            'dedup_key' => "tunggu_ump_{$ump->id}_ttd_kadiv_1",
        ]);

        // UmpRemind command test: ubah jatuh tempo menjadi kemarin (lewat)
        $ump->jatuh_tempo = Carbon::yesterday()->toDateString();
        $ump->save();

        $this->artisan('ump:remind')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->staffUser->id,
            'type' => 'ump_tenggat',
            'dedup_key' => "ump_tenggat_{$ump->id}_lewat",
        ]);
    }

    public function test_ump_remind_uses_configurable_threshold(): void
    {
        Setting::set('ump.tenggat_hari', 14);
        Setting::set('ump.pengingat_hari', 5);

        $permohonan = $this->createPermohonanDraft(1000000);
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);
        $workflow->approve($permohonan, $this->kadivUser);
        $workflow->approve($permohonan, $this->financeUser);
        $pencairan = $workflow->cairkan($permohonan, $this->financeUser, [
            'no_ppd' => 'PPD-2026-888',
            'tgl_cair' => now()->toDateString(),
        ]);

        $ump = $pencairan->ump;
        // Jatuh tempo 5 hari lagi (H-5)
        $ump->jatuh_tempo = Carbon::today()->addDays(5)->toDateString();
        $ump->save();

        $this->artisan('ump:remind')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->staffUser->id,
            'type' => 'ump_tenggat',
            'dedup_key' => "ump_tenggat_{$ump->id}_mendekati",
        ]);
    }

    public function test_permohonan_remind_sends_notification_and_is_idempotent(): void
    {
        Setting::set('permohonan.pengingat_hari', 3);

        $permohonan = $this->createPermohonanDraft(1500000);
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);

        // Manipulasi updated_at jadi 4 hari lalu
        $permohonan->timestamps = false;
        $permohonan->updated_at = Carbon::today()->subDays(4);
        $permohonan->save();
        $permohonan->timestamps = true;

        $this->artisan('permohonan:remind')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $permohonan->atasan_id,
            'type' => 'permohonan_tertunda',
            'dedup_key' => "pengingat_permohonan_{$permohonan->id}_diajukan",
        ]);

        $countBefore = DB::table('notifications')->where('type', 'permohonan_tertunda')->count();

        // Run lagi, harus idempoten (tidak menambah notifikasi)
        $this->artisan('permohonan:remind')->assertSuccessful();
        $countAfter = DB::table('notifications')->where('type', 'permohonan_tertunda')->count();

        $this->assertEquals($countBefore, $countAfter);
    }

    public function test_permohonan_remind_skips_when_disabled(): void
    {
        Setting::set('permohonan.pengingat_hari', 0);

        $permohonan = $this->createPermohonanDraft(1500000);
        $workflow = new PermohonanWorkflow;
        $workflow->submit($permohonan);

        $permohonan->timestamps = false;
        $permohonan->updated_at = Carbon::today()->subDays(5);
        $permohonan->save();
        $permohonan->timestamps = true;

        $this->artisan('permohonan:remind')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', [
            'type' => 'permohonan_tertunda',
        ]);
    }

    public function test_notification_bell_component_renders_and_marks_as_read(): void
    {
        // Kirim 2 notifikasi ke user
        NotificationService::kirim(
            $this->staffUser,
            'dokumen_diputuskan',
            ['judul' => 'Notif 1', 'pesan' => 'Pesan 1', 'url' => '/dashboard'],
            'test_notif_1'
        );
        NotificationService::kirim(
            $this->staffUser,
            'dokumen_diputuskan',
            ['judul' => 'Notif 2', 'pesan' => 'Pesan 2', 'url' => '/dashboard'],
            'test_notif_2'
        );

        Livewire::actingAs($this->staffUser)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 2)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', true)
            ->assertSee('Notif 1')
            ->assertSee('Notif 2')
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);

        $this->assertEquals(0, $this->staffUser->unreadNotifications()->count());
    }

    public function test_notification_service_sends_email_when_setting_is_active(): void
    {
        Mail::fake();
        Setting::set('notifikasi.email_aktif', 'aktif');

        NotificationService::kirim(
            $this->staffUser,
            'permohonan_tertunda',
            [
                'judul' => 'Pengingat Permohonan',
                'pesan' => 'Permohonan tertunda 3 hari',
                'url' => 'http://localhost/permohonan/1',
            ],
            'dedup_email_test_1'
        );

        Mail::assertSent(ReminderNotificationMail::class, function ($mail) {
            return $mail->hasTo($this->staffUser->email)
                && $mail->data['judul'] === 'Pengingat Permohonan';
        });
    }

    public function test_notification_service_does_not_send_email_when_setting_is_disabled(): void
    {
        Mail::fake();
        Setting::set('notifikasi.email_aktif', 'nonaktif');

        NotificationService::kirim(
            $this->staffUser,
            'permohonan_tertunda',
            [
                'judul' => 'Pengingat Permohonan',
                'pesan' => 'Permohonan tertunda 3 hari',
                'url' => 'http://localhost/permohonan/1',
            ],
            'dedup_email_test_2'
        );

        Mail::assertNothingSent();
    }

    public function test_notification_service_does_not_duplicate_email_on_repeated_dedup_key(): void
    {
        Mail::fake();
        Setting::set('notifikasi.email_aktif', 'aktif');

        NotificationService::kirim(
            $this->staffUser,
            'permohonan_tertunda',
            ['judul' => 'Pengingat', 'pesan' => 'Pesan', 'url' => '/test'],
            'dedup_email_duplicate'
        );

        // Kirim ulang dengan dedup_key yang sama
        NotificationService::kirim(
            $this->staffUser,
            'permohonan_tertunda',
            ['judul' => 'Pengingat', 'pesan' => 'Pesan', 'url' => '/test'],
            'dedup_email_duplicate'
        );

        Mail::assertSent(ReminderNotificationMail::class, 1);
    }
}
