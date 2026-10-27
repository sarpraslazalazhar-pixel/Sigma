<?php

namespace Tests\Feature;

use App\Livewire\Master\AuditLogIndex;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $keuanganUser;

    protected User $unitUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->keuanganUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->unitUser = User::where('username', 'subakti')->firstOrFail();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/master/audit-logs');

        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_receives_forbidden(): void
    {
        $response = $this->actingAs($this->unitUser)->get('/master/audit-logs');

        $response->assertStatus(403);
    }

    public function test_admin_and_keuangan_can_access_audit_log_page(): void
    {
        $adminResponse = $this->actingAs($this->adminUser)->get('/master/audit-logs');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Jejak Audit (Audit Trail)');

        $keuanganResponse = $this->actingAs($this->keuanganUser)->get('/master/audit-logs');
        $keuanganResponse->assertStatus(200);
        $keuanganResponse->assertSee('Jejak Audit (Audit Trail)');
    }

    public function test_audit_logs_rendered_correctly(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'kode_anggaran.created',
            'entity_type' => 'App\Models\KodeAnggaran',
            'entity_id' => 999,
            'before' => null,
            'after' => ['kode' => '9.9.9', 'nama' => 'Testing Audit Pos'],
            'ip' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 TestBrowser',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogIndex::class)
            ->assertSee('kode_anggaran.created')
            ->assertSee('KodeAnggaran')
            ->assertSee('999')
            ->assertSee($this->adminUser->name)
            ->assertSee('127.0.0.1');
    }

    public function test_filtering_audit_logs_by_search_and_action(): void
    {
        $log1 = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'user.created',
            'entity_type' => 'App\Models\User',
            'entity_id' => 12,
            'before' => null,
            'after' => ['username' => 'user.baru'],
            'ip' => '10.0.0.1',
        ]);

        $log2 = AuditLog::create([
            'user_id' => $this->keuanganUser->id,
            'action' => 'kode_anggaran.disahkan',
            'entity_type' => 'App\Models\KodeAnggaran',
            'entity_id' => 34,
            'before' => ['status' => 'draft'],
            'after' => ['status' => 'disahkan'],
            'ip' => '10.0.0.2',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogIndex::class)
            ->set('search', 'KodeAnggaran')
            ->assertSee('kode_anggaran.disahkan')
            ->assertDontSee('user.created')
            ->set('search', '')
            ->set('actionFilter', 'disahkan')
            ->assertSee('kode_anggaran.disahkan')
            ->assertDontSee('user.created')
            ->set('actionFilter', '')
            ->set('userFilter', (string) $this->adminUser->id)
            ->assertSee('user.created')
            ->assertDontSee('kode_anggaran.disahkan')
            ->call('resetFilters')
            ->assertSee('user.created')
            ->assertSee('kode_anggaran.disahkan');
    }

    public function test_diff_modal_can_be_opened_and_shows_before_after(): void
    {
        $updateLog = AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'kode_anggaran.updated',
            'entity_type' => 'App\Models\KodeAnggaran',
            'entity_id' => 77,
            'before' => [
                'nama' => 'Plafon Lama',
                'plafon' => 15000000,
            ],
            'after' => [
                'nama' => 'Plafon Baru',
                'plafon' => 25000000,
            ],
            'ip' => '192.168.1.50',
            'user_agent' => 'Mozilla/5.0 AgentTest',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(AuditLogIndex::class)
            ->assertSet('showDiffModal', false)
            ->call('openDiff', $updateLog->id)
            ->assertSet('showDiffModal', true)
            ->assertSet('selectedAuditId', $updateLog->id)
            ->assertSee('Rincian Jejak Audit #'.$updateLog->id)
            ->assertSee('Plafon Lama')
            ->assertSee('Plafon Baru')
            ->assertSee('15000000')
            ->assertSee('25000000')
            ->assertSee('Berubah')
            ->call('closeDiffModal')
            ->assertSet('showDiffModal', false);
    }
}
