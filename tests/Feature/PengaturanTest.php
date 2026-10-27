<?php

namespace Tests\Feature;

use App\Livewire\Master\PengaturanIndex;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->staffUser = User::where('username', 'subakti')->first();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('master.pengaturan'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_gets_forbidden(): void
    {
        $response = $this->actingAs($this->staffUser)->get(route('master.pengaturan'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_pengaturan_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('master.pengaturan'));
        $response->assertOk();
        $response->assertSee('Pengaturan Sistem');
        $response->assertSee('Ambang Peringatan Limit (%)');
        $response->assertSee('Pengingat UMP Sebelum Jatuh Tempo (Hari)');
        $response->assertSee('Pengingat Permohonan Tertunda (Hari)');
        $response->assertSee('Notifikasi Email');
    }

    public function test_admin_can_update_settings(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(PengaturanIndex::class)
            ->set('values.ump.pengingat_hari', 7)
            ->set('values.permohonan.pengingat_hari', 5)
            ->set('values.notifikasi.email_aktif', 'aktif')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertEquals(7, Setting::get('ump.pengingat_hari'));
        $this->assertEquals(5, Setting::get('permohonan.pengingat_hari'));
        $this->assertEquals('aktif', Setting::get('notifikasi.email_aktif'));
    }

    public function test_admin_can_reset_settings_to_defaults(): void
    {
        Setting::set('ump.pengingat_hari', 10);

        Livewire::actingAs($this->adminUser)
            ->test(PengaturanIndex::class)
            ->call('resetToDefaults')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(3, Setting::get('ump.pengingat_hari'));
    }
}
