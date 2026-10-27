<?php

namespace Tests\Feature;

use App\Livewire\Master\UserIndex;
use App\Models\Leveling;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $unitUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->unitUser = User::where('username', 'subakti')->first();
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $this->actingAs($this->unitUser)
            ->get('/master/users')
            ->assertStatus(403);
    }

    public function test_admin_can_access_user_management(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/master/users')
            ->assertStatus(200)
            ->assertSee('Manajemen Pengguna & Leveling')
            ->assertSee('admin')
            ->assertSee('subakti');
    }

    public function test_admin_can_create_new_user(): void
    {
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->first();
        $leveling = Leveling::where('urutan', 2)->first();

        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openCreateModal')
            ->set('form_name', 'Budi Santoso')
            ->set('form_username', 'budi.santoso')
            ->set('form_email', 'budi@alazharpeduli.or.id')
            ->set('form_password', 'secret123')
            ->set('form_role', 'unit')
            ->set('form_unit_id', $unit->id)
            ->set('form_leveling_id', $leveling->id)
            ->set('form_jabatan', 'Staf Kelembagaan')
            ->set('form_status_kepegawaian', 'Tetap')
            ->set('form_is_petugas_input', false)
            ->set('form_is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Budi Santoso',
            'username' => 'budi.santoso',
            'email' => 'budi@alazharpeduli.or.id',
            'role' => 'unit',
            'unit_id' => $unit->id,
            'leveling_id' => $leveling->id,
            'jabatan' => 'Staf Kelembagaan',
            'is_active' => true,
        ]);

        $newUser = User::where('username', 'budi.santoso')->first();
        $this->assertTrue(Hash::check('secret123', $newUser->password));
    }

    public function test_admin_can_edit_existing_user(): void
    {
        $user = User::where('username', 'deden.salim')->first();

        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openEditModal', $user->id)
            ->set('form_name', 'Deden N. Salim, MM')
            ->set('form_jabatan', 'Senior Manager HRD')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Deden N. Salim, MM', $user->fresh()->name);
        $this->assertEquals('Senior Manager HRD', $user->fresh()->jabatan);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $user = User::where('username', 'deden.salim')->first();
        $this->assertTrue($user->is_active);

        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('toggleActive', $user->id);

        $this->assertFalse($user->fresh()->is_active);

        // Admin cannot toggle own account
        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('toggleActive', $this->adminUser->id);

        $this->assertTrue($this->adminUser->fresh()->is_active);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $user = User::where('username', 'subakti')->first();

        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openResetPasswordModal', $user->id)
            ->set('resetNewPassword', 'newpassword123')
            ->call('saveResetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
