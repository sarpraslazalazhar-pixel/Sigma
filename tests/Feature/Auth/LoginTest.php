<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected User $activeUser;

    protected User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeUser = User::create([
            'name' => 'Staf TU',
            'username' => 'tu.sekretariat',
            'email' => 'tu@alazharpeduli.or.id',
            'password' => Hash::make('password'),
            'role' => 'unit',
            'jabatan' => 'Staf Tata Usaha',
            'is_petugas_input' => true,
            'is_active' => true,
        ]);

        $this->inactiveUser = User::create([
            'name' => 'User Nonaktif',
            'username' => 'nonaktif',
            'email' => 'nonaktif@alazharpeduli.or.id',
            'password' => Hash::make('password'),
            'role' => 'unit',
            'is_active' => false,
        ]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SIGMA');
        $response->assertSee('Masuk ke Sistem');
    }

    public function test_unauthenticated_user_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_user_can_login_using_username(): void
    {
        Livewire::test('auth.login')
            ->set('login_id', 'tu.sekretariat')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->activeUser);
    }

    public function test_user_can_login_using_email(): void
    {
        Livewire::test('auth.login')
            ->set('login_id', 'tu@alazharpeduli.or.id')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->activeUser);
    }

    public function test_inactive_user_cannot_login(): void
    {
        Livewire::test('auth.login')
            ->set('login_id', 'nonaktif')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['login_id']);

        $this->assertGuest();
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        Livewire::test('auth.login')
            ->set('login_id', 'tu.sekretariat')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors(['login_id']);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAs($this->activeUser);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_use_fill_demo_helper(): void
    {
        Livewire::test('auth.login')
            ->call('fillDemo', 'admin')
            ->assertSet('login_id', 'admin')
            ->assertSet('password', 'password');
    }

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->activeUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang, Staf TU');
        $response->assertSee('Kondisi Anggaran Tahun');
        $response->assertSee('Plafon RAB');
        $response->assertSee('Sisa Tersedia');
    }
}
