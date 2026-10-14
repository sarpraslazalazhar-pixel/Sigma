<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Setting;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BudgetCalculator;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\KodeAnggaranSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
            KodeAnggaranSeeder::class,
        ]);
    }

    public function test_master_data_seeded_properly(): void
    {
        $this->assertDatabaseHas('tahun_anggaran', ['tahun' => 2026, 'status' => 'aktif']);
        $this->assertDatabaseHas('sumber_dana', ['nama' => 'Zakat', 'butuh_asnaf' => true]);
        $this->assertDatabaseHas('units', ['kode' => 'DIV-SEK', 'tipe' => 'divisi']);
        $this->assertDatabaseHas('units', ['kode' => 'UNT-SEK-DKL', 'tipe' => 'unit']);
        $this->assertDatabaseHas('levelings', ['nama' => 'Supervisor', 'urutan' => 1]);
        $this->assertDatabaseHas('levelings', ['nama' => 'Manager', 'urutan' => 2]);
        $this->assertDatabaseHas('levelings', ['nama' => 'Kepala Divisi / Direksi', 'urutan' => 3, 'batas_atas' => null]);
        $this->assertDatabaseMissing('levelings', ['urutan' => 4]);
    }

    public function test_setting_helper_get_and_set(): void
    {
        $this->assertEquals(80, Setting::get('ambang.limit'));
        $this->assertEquals(14, Setting::get('ump.tenggat_hari'));

        Setting::set('custom.test_key', 'test_value');
        $this->assertEquals('test_value', Setting::get('custom.test_key'));
        $this->assertEquals('fallback', Setting::get('non_existent', 'fallback'));
    }

    public function test_audit_log_is_append_only(): void
    {
        $log = AuditLog::create([
            'action' => 'test.create',
            'entity_type' => 'App\Models\Unit',
            'entity_id' => 1,
            'before' => null,
            'after' => ['name' => 'Unit Test'],
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'test.create']);

        // Expect update to fail
        $this->expectException(RuntimeException::class);
        $log->update(['action' => 'test.modified']);
    }

    public function test_audit_log_delete_throws_exception(): void
    {
        $log = AuditLog::create([
            'action' => 'test.delete_protect',
            'entity_type' => 'App\Models\Unit',
            'entity_id' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $log->delete();
    }

    public function test_audit_logger_service_works(): void
    {
        $logger = new AuditLogger();
        $user = User::first();
        $this->actingAs($user);

        $log = $logger->log('unit.updated', $user->unit, ['nama' => 'Lama'], ['nama' => 'Baru']);

        $this->assertNotNull($log->id);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('unit.updated', $log->action);
    }

    protected function createSampleKodeAnggaran(int $taId): array
    {
        $node1 = KodeAnggaran::create([
            'tahun_anggaran_id' => $taId,
            'kode' => '1',
            'nama' => 'Beban Operasional & Program',
            'depth' => 1,
            'plafon' => 500000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $node11 = KodeAnggaran::create([
            'tahun_anggaran_id' => $taId,
            'parent_id' => $node1->id,
            'kode' => '1.1',
            'nama' => 'Kesekretariatan & Administrasi',
            'depth' => 2,
            'plafon' => 200000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $node111 = KodeAnggaran::create([
            'tahun_anggaran_id' => $taId,
            'parent_id' => $node11->id,
            'kode' => '1.1.1',
            'nama' => 'Operasional Kantor & IT',
            'depth' => 3,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $node12 = KodeAnggaran::create([
            'tahun_anggaran_id' => $taId,
            'parent_id' => $node1->id,
            'kode' => '1.2',
            'nama' => 'Program Dakwah & Sosial',
            'depth' => 2,
            'plafon' => 200000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        return [$node1, $node11, $node111, $node12];
    }

    public function test_kode_anggaran_relations_and_is_leaf(): void
    {
        $ta = TahunAnggaran::where('tahun', 2026)->first();
        [$node1, $node11, $leaf] = $this->createSampleKodeAnggaran($ta->id);

        $this->assertFalse($node1->isLeaf());
        $this->assertTrue($leaf->isLeaf());
        $this->assertCount(2, $node1->children);
        $this->assertEquals($node1->id, $leaf->parent->parent->id);
    }

    public function test_budget_calculator_calculates_tree(): void
    {
        $ta = TahunAnggaran::where('tahun', 2026)->first();
        [$node1, $node11, $leaf] = $this->createSampleKodeAnggaran($ta->id);

        $calculator = new BudgetCalculator();
        $calculated = $calculator->calculateTree($ta->id);

        $this->assertNotEmpty($calculated);

        // Node 1: Plafon 500.000.000, Anak: 1.1 (200jt) + 1.2 (200jt) = 400jt
        // Belum dialokasikan = 100.000.000
        $calc1 = $calculated->get($node1->id);

        $this->assertEquals(500000000, $calc1['effective_budget']);
        $this->assertEquals(400000000, $calc1['total_anak']);
        $this->assertEquals(100000000, $calc1['belum_dialokasikan']);
        $this->assertEquals('Aman', $calc1['status']);

        // Leaf node 1.1.1: Plafon 50.000.000
        $calcLeaf = $calculated->get($leaf->id);

        $this->assertEquals(50000000, $calcLeaf['effective_budget']);
        $this->assertEquals(0, $calcLeaf['total_anak']);
        $this->assertEquals(0, $calcLeaf['belum_dialokasikan']);
        $this->assertTrue($calcLeaf['is_leaf']);
        $this->assertEquals('Aman', $calcLeaf['status']);
    }

    public function test_user_relations(): void
    {
        $user = User::where('username', 'tu.sekretariat')->first();
        $this->assertNotNull($user->unit);
        $this->assertEquals('UNT-SEK-DKL', $user->unit->kode);
        $this->assertNotNull($user->leveling);
        $this->assertEquals(1, $user->leveling->urutan);
    }
}
