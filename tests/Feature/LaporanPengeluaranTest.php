<?php

namespace Tests\Feature;

use App\Exports\LaporanPengeluaranExport;
use App\Livewire\Reports\LaporanPengeluaranIndex;
use App\Models\KodeAnggaran;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\BudgetCalculator;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LaporanPengeluaranTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected TahunAnggaran $ta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('username', 'admin')->first();
        $this->ta = TahunAnggaran::where('status', 'aktif')->first();

        // Sample isolated tree
        $node24 = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '2.4',
            'nama' => 'Penyaluran Dana Amil',
            'depth' => 1,
            'plafon' => 5000000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $node241 = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $node24->id,
            'kode' => '2.4.1',
            'nama' => 'Biaya Kepegawaian',
            'depth' => 2,
            'plafon' => 4560000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $node241->id,
            'kode' => '2.4.1.1',
            'nama' => 'Gaji Amil',
            'depth' => 3,
            'plafon' => 4560000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    public function test_can_render_laporan_pengeluaran_page(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/laporan/pengeluaran-bulanan')
            ->assertStatus(200)
            ->assertSee('Laporan Pengeluaran Bulanan')
            ->assertSee('2.4')
            ->assertSee('Gaji Amil');
    }

    public function test_calculator_monthly_expenditure_matrix_structure(): void
    {
        $calculator = new BudgetCalculator;
        $report = $calculator->getMonthlyExpenditureReport($this->ta->id);

        $this->assertCount(3, $report);

        $leaf = $report->firstWhere('kode', '2.4.1.1');
        $this->assertNotNull($leaf);
        $this->assertEquals(4560000000, $leaf['plafon_rab']);
        $this->assertCount(12, $leaf['months']);
        $this->assertEquals(0, $leaf['total_pengeluaran']);
        $this->assertEquals(4560000000, $leaf['sisa_anggaran']);
        $this->assertEquals(0, $leaf['persentase_penyerapan']);
    }

    public function test_can_export_excel_laporan_pengeluaran(): void
    {
        Excel::fake();

        Livewire::actingAs($this->adminUser)
            ->test(LaporanPengeluaranIndex::class)
            ->call('exportExcel');

        Excel::assertDownloaded("Laporan_Pengeluaran_Bulanan_{$this->ta->tahun}.xlsx", function (LaporanPengeluaranExport $export) {
            $data = $export->collection();

            return $data->count() === 3;
        });
    }

    public function test_export_headings_and_styles(): void
    {
        $export = new LaporanPengeluaranExport($this->ta->id);
        $headings = $export->headings();

        $this->assertContains('KODE ANGGARAN', $headings);
        $this->assertContains('JANUARI', $headings);
        $this->assertContains('DESEMBER', $headings);
        $this->assertContains('TOTAL PENGELUARAN', $headings);
        $this->assertContains('PENYERAPAN ANGGARAN', $headings);
    }
}
