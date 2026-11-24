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

    public function test_sekretariat_users_are_sorted_hierarchically_by_divisi_unit_and_atasan(): void
    {
        $divSek = Unit::where('kode', 'DIV-SEK')->firstOrFail();
        $sekUsers = User::with(['unit.parent', 'leveling', 'atasan'])
            ->whereIn('unit_id', $divSek->getSameDivisiUnitIds())
            ->get();

        $sorted = User::sortHierarchically($sekUsers);
        $usernames = $sorted->pluck('username')->all();

        $pos = static fn (string $u): int => array_search($u, $usernames, true);

        // Divisi Sekretariat -> Diklat & Litbang -> Humas, GA, dan IT -> Kelembagaan
        $this->assertLessThan($pos('dedy.irwansyah'), $pos('rahmatullah.sidik'));
        $this->assertLessThan($pos('lias'), $pos('dedy.irwansyah'));
        $this->assertLessThan($pos('ahmad.zaki'), $pos('lias'));
        $this->assertLessThan($pos('sigit.nugroho'), $pos('ahmad.zaki'));
        $this->assertLessThan($pos('deden.salim'), $pos('sigit.nugroho'));
        $this->assertLessThan($pos('anas.priyogi'), $pos('deden.salim'));
        $this->assertLessThan($pos('jihan.sahra'), $pos('anas.priyogi'));
        $this->assertLessThan($pos('subakti'), $pos('anas.priyogi'));

        // Di dalam Humas, GA, dan IT: setiap tim tepat berada di bawah koordinatornya (contiguous subtree)
        $itTeam = ['rudiansah', 'gibran.heryadi', 'stiyadi', 'admin'];
        $itPositions = array_map($pos, $itTeam);
        $this->assertSame($pos('rudiansah'), min($itPositions));
        $this->assertSame(count($itTeam) - 1, max($itPositions) - min($itPositions));

        $gaTeam = ['suryamin', 'hadi.cahyo', 'eko.mustakim', 'muh.sobran', 'ahmad.rizal', 'zaenal.mustofa'];
        $gaPositions = array_map($pos, $gaTeam);
        $this->assertSame($pos('suryamin'), min($gaPositions));
        $this->assertSame(count($gaTeam) - 1, max($gaPositions) - min($gaPositions));

        $humasTeam = ['bayu.setiawan', 'nopen.setiawan', 'nadhilah.sifa', 'rosyadi', 'fauzi.suhada', 'fitri.wulandari', 'risdawati'];
        $humasPositions = array_map($pos, $humasTeam);
        $this->assertSame($pos('bayu.setiawan'), min($humasPositions));
        $this->assertSame(count($humasTeam) - 1, max($humasPositions) - min($humasPositions));

        // Pastikan Gibran Deniz Heryadi & jabatan Lias / Bayu sesuai struktur terbaru
        $gibran = User::where('username', 'gibran.heryadi')->firstOrFail();
        $this->assertSame('Gibran Deniz Heryadi', $gibran->name);
        $this->assertSame('Relawan IT Developer', $gibran->jabatan);
        $this->assertSame('Relawan', $gibran->status_kepegawaian);
        $this->assertSame('rudiansah', $gibran->atasan?->username);

        $lias = User::where('username', 'lias')->firstOrFail();
        $this->assertSame('Koordinator Diklat Litbang', $lias->jabatan);
        $this->assertSame('dedy.irwansyah', $lias->atasan?->username);
    }

    public function test_admin_can_assign_atasan_and_cannot_create_hierarchy_cycle(): void
    {
        $sigit = User::where('username', 'sigit.nugroho')->firstOrFail();
        $rudiansah = User::where('username', 'rudiansah')->firstOrFail();
        $stiyadi = User::where('username', 'stiyadi')->firstOrFail();

        // Tidak boleh menunjuk diri sendiri sebagai atasan
        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openEditModal', $rudiansah->id)
            ->set('form_atasan_id', $rudiansah->id)
            ->call('save')
            ->assertHasErrors(['form_atasan_id']);

        // Tidak boleh menunjuk bawahan tidak langsung (stiyadi -> rudiansah -> sigit) sebagai atasan sigit
        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openEditModal', $sigit->id)
            ->set('form_atasan_id', $stiyadi->id)
            ->call('save')
            ->assertHasErrors(['form_atasan_id']);

        // Menunjuk atasan valid (misal ubah atasan stiyadi ke sigit) berhasil
        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openEditModal', $stiyadi->id)
            ->set('form_atasan_id', $sigit->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($sigit->id, $stiyadi->fresh()->atasan_id);
    }

    public function test_divisi_filter_includes_child_unit_users_and_long_jabatan_saves(): void
    {
        $divSek = Unit::where('kode', 'DIV-SEK')->firstOrFail();
        $kadivSek = User::where('username', 'rahmatullah.sidik')->firstOrFail();

        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->set('unitFilter', (string) $divSek->id)
            ->assertSee('Rahmatullah Sidik')
            ->assertSee('Gibran Deniz Heryadi')
            ->assertSee('Subakti')
            ->assertDontSee('Direktur Keuangan');

        // Rahmatullah Sidik memiliki jabatan > 100 karakter; pastikan edit & simpan tidak gagal validasi
        Livewire::actingAs($this->adminUser)
            ->test(UserIndex::class)
            ->call('openEditModal', $kadivSek->id)
            ->call('save')
            ->assertHasNoErrors();
    }
}
