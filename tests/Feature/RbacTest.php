<?php

namespace Tests\Feature;

use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $keuangan;

    protected User $kadivSekretariat;

    protected User $managerKlm;

    protected User $staffKlm;

    protected User $managerProgram;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->admin = User::where('username', 'admin')->firstOrFail();
        $this->keuangan = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->kadivSekretariat = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $this->managerKlm = User::where('username', 'deden.salim')->firstOrFail();
        $this->staffKlm = User::where('username', 'subakti')->firstOrFail();
        $this->managerProgram = User::where('username', 'manager.program')->firstOrFail();
    }

    public function test_user_rbac_helper_methods(): void
    {
        // Admin
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->hasRole('admin'));
        $this->assertTrue($this->admin->hasRole(['admin', 'keuangan']));
        $this->assertFalse($this->admin->isKeuangan());
        $this->assertFalse($this->admin->isUnit());

        // Keuangan
        $this->assertTrue($this->keuangan->isKeuangan());
        $this->assertTrue($this->keuangan->hasRole('keuangan'));
        $this->assertFalse($this->keuangan->isAdmin());

        // Leveling helpers
        $this->assertTrue($this->kadivSekretariat->isKadiv());
        $this->assertFalse($this->kadivSekretariat->isManager());

        $this->assertTrue($this->managerKlm->isManager());
        $this->assertFalse($this->managerKlm->isKadiv());

        $this->assertTrue($this->staffKlm->isSupervisor());
    }

    public function test_division_access_helper(): void
    {
        $divSek = Unit::where('kode', 'DIV-SEK')->firstOrFail();
        $divPrg = Unit::where('kode', 'DIV-PRG')->firstOrFail();

        // Admin & Keuangan bisa akses semua divisi
        $this->assertTrue($this->admin->canAccessDivision($divSek->id));
        $this->assertTrue($this->admin->canAccessDivision($divPrg->id));
        $this->assertTrue($this->keuangan->canAccessDivision($divSek->id));
        $this->assertTrue($this->keuangan->canAccessDivision($divPrg->id));

        // Anggota Divisi Sekretariat hanya bisa akses Divisi Sekretariat
        $this->assertTrue($this->managerKlm->canAccessDivision($divSek->id));
        $this->assertFalse($this->managerKlm->canAccessDivision($divPrg->id));

        // Anggota Divisi Program hanya bisa akses Divisi Program
        $this->assertTrue($this->managerProgram->canAccessDivision($divPrg->id));
        $this->assertFalse($this->managerProgram->canAccessDivision($divSek->id));
    }

    public function test_rbac_gates(): void
    {
        // Super-admin passes all gates
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-users'));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view-audit-logs'));
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-budget'));
        $this->assertTrue(Gate::forUser($this->admin)->allows('cairkan-dana'));

        // Keuangan passes finance & budget gates, but not user management
        $this->assertFalse(Gate::forUser($this->keuangan)->allows('manage-users'));
        $this->assertTrue(Gate::forUser($this->keuangan)->allows('view-audit-logs'));
        $this->assertTrue(Gate::forUser($this->keuangan)->allows('manage-budget'));
        $this->assertTrue(Gate::forUser($this->keuangan)->allows('cairkan-dana'));
        $this->assertTrue(Gate::forUser($this->keuangan)->allows('verifikasi-keuangan'));

        // Unit user cannot manage users or view audit logs
        $this->assertFalse(Gate::forUser($this->staffKlm)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($this->staffKlm)->allows('view-audit-logs'));
        $this->assertFalse(Gate::forUser($this->staffKlm)->allows('manage-budget'));
        $this->assertTrue(Gate::forUser($this->staffKlm)->allows('create-permohonan'));

        // Viewer user
        $viewer = User::create([
            'name' => 'Auditor Eksternal',
            'username' => 'auditor',
            'email' => 'auditor@alazhar.id',
            'password' => 'password',
            'role' => 'viewer',
            'is_active' => true,
        ]);
        $this->assertFalse(Gate::forUser($viewer)->allows('create-permohonan'));
    }

    public function test_route_middleware_blocks_unauthorized_roles(): void
    {
        // 1. /master/users: Hanya admin yang diizinkan (403 untuk staf / keuangan)
        $this->actingAs($this->admin)->get('/master/users')->assertOk();
        $this->actingAs($this->keuangan)->get('/master/users')->assertStatus(403);
        $this->actingAs($this->staffKlm)->get('/master/users')->assertStatus(403);

        // 2. /master/audit-logs: Hanya admin dan keuangan yang diizinkan
        $this->actingAs($this->admin)->get('/master/audit-logs')->assertOk();
        $this->actingAs($this->keuangan)->get('/master/audit-logs')->assertOk();
        $this->actingAs($this->staffKlm)->get('/master/audit-logs')->assertStatus(403);

        // 3. /keuangan/verifikasi: Hanya admin/keuangan/petugas yang diizinkan
        $plainUnitUser = User::where('username', 'rudiansah')->firstOrFail(); // is_petugas_input = false
        $this->actingAs($this->admin)->get('/keuangan/verifikasi')->assertOk();
        $this->actingAs($this->keuangan)->get('/keuangan/verifikasi')->assertOk();
        $this->actingAs($plainUnitUser)->get('/keuangan/verifikasi')->assertStatus(403);
    }

    public function test_permohonan_policy_rules(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kode = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '99.1',
            'nama' => 'RBAC Policy Testing',
            'depth' => 1,
            'plafon' => 10000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $permohonanSek = Permohonan::create([
            'no_pengajuan' => 'RBAC-SEK-001',
            'unit_id' => $this->managerKlm->unit_id,
            'user_id' => $this->managerKlm->id,
            'kode_anggaran_id' => $kode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan Divisi Sekretariat',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'diajukan',
            'total_diminta' => 5000000,
        ]);

        // Rekan satu divisi (Staff KLM) & Admin & Keuangan bisa view
        $this->assertTrue($this->admin->can('view', $permohonanSek));
        $this->assertTrue($this->keuangan->can('view', $permohonanSek));
        $this->assertTrue($this->staffKlm->can('view', $permohonanSek));
        $this->assertTrue($this->kadivSekretariat->can('view', $permohonanSek));

        // Pengguna dari divisi berbeda TIDAK BISA view
        $this->assertFalse($this->managerProgram->can('view', $permohonanSek));

        // Kadiv Divisi Sekretariat BISA memeriksa (approveKadiv)
        $this->assertTrue($this->kadivSekretariat->can('approveKadiv', $permohonanSek));

        // Manager / Staff / User dari divisi berbeda TIDAK BISA approveKadiv
        $this->assertFalse($this->managerKlm->can('approveKadiv', $permohonanSek));
        $this->assertFalse($this->managerProgram->can('approveKadiv', $permohonanSek));
    }
}
