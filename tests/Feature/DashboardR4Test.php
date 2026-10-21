<?php

namespace Tests\Feature;

use App\Exports\RekapAnggaranExport;
use App\Exports\RekapPermohonanExport;
use App\Exports\RekapPpdUmpExport;
use App\Exports\RekapRealisasiBulananExport;
use App\Livewire\Dashboard;
use App\Livewire\Laporan\LaporanMonitoringIndex;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use App\Services\UmpWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DashboardR4Test extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected User $managerUser;

    protected User $kadivUser;

    protected User $financeUser;

    protected User $adminUser;

    protected TahunAnggaran $ta;

    protected Unit $unitKlm;

    protected KodeAnggaran $rootNode;

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
        $this->financeUser = User::where('username', 'rochadi.kohar')->first();
        $this->adminUser = User::where('username', 'admin')->first();

        $this->ta = TahunAnggaran::where('status', 'aktif')->first();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->first();
        $this->sumberDana = SumberDana::first();

        $this->rootNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '2',
            'nama' => 'Penyaluran',
            'depth' => 1,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $this->rootNode->id,
            'kode' => '2.1',
            'nama' => 'Penyaluran Program',
            'depth' => 2,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    private function createAndCompleteCycle(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Bantuan',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'atasan_id' => $this->managerUser->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'nama_kegiatan' => 'Kegiatan Dashboard Test',
            'latar_belakang' => 'Testing Dashboard',
            'total_diminta' => 10000000,
            'status' => 'draft',
            'tgl_mulai' => now()->toDateString(),
            'tgl_selesai' => now()->addDays(2)->toDateString(),
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item 1',
            'volume' => 1,
            'satuan' => 'Paket',
            'harga_satuan' => 10000000,
            'jumlah_diminta' => 10000000,
        ]);

        $pWorkflow = new PermohonanWorkflow;
        $pWorkflow->submit($permohonan);
        $pWorkflow->approve($permohonan, $this->managerUser);
        $pWorkflow->approve($permohonan, $this->kadivUser);
        $pWorkflow->approve($permohonan, $this->financeUser);
        $pencairan = $pWorkflow->cairkan($permohonan, $this->financeUser, [
            'no_ppd' => 'PPD-DSH-001',
            'tgl_cair' => now()->toDateString(),
        ]);

        $ump = $pencairan->ump;
        $uWorkflow = new UmpWorkflow;
        $uWorkflow->saveItems($ump, [
            [
                'tgl' => now()->toDateString(),
                'rincian' => 'Pengeluaran UMP Test',
                'no_bukti' => 'BKT-01',
                'jumlah' => 10000000,
            ],
        ]);
        $uWorkflow->submitByPj($ump, $this->staffUser, 'Realisasi selesai');
        $uWorkflow->approve($ump, $this->kadivUser);
        $uWorkflow->approve($ump, $this->financeUser);
        $uWorkflow->approve($ump, $this->financeUser, [
            'scan_final' => UploadedFile::fake()->create('scan.pdf', 100),
        ]);
    }

    public function test_dashboard_renders_kpi_cards_with_real_data(): void
    {
        $this->createAndCompleteCycle();

        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('50.000.000') // Budget disahkan
            ->assertSee('10.000.000') // Realisasi
            ->assertSee('20%') // Serapan 10jt / 50jt = 20%
            ->assertSee('40.000.000'); // Sisa tersedia: 50jt - 10jt = 40jt
    }

    public function test_dashboard_filter_by_unit_hides_budget_cards(): void
    {
        $this->createAndCompleteCycle();

        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->set('unitId', $this->unitKlm->id)
            ->assertOk()
            ->assertSee('N/A (filter divisi)')
            ->assertSee('10.000.000'); // Realisasi tetap tampil
    }

    public function test_monitoring_table_shows_all_nodes_and_supports_search(): void
    {
        $this->createAndCompleteCycle();

        Livewire::actingAs($this->adminUser)
            ->test(Dashboard::class)
            ->assertSee('2.1')
            ->assertSee('Penyaluran Program')
            ->set('search', 'NonExistentXYZ')
            ->assertSee('Tidak ada data pos anggaran');
    }

    public function test_antrean_persetujuan_lists_documents_for_atasan(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => '',
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Bantuan',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'atasan_id' => $this->managerUser->id,
            'sumber_dana_id' => $this->sumberDana->id,
            'nama_kegiatan' => 'Antrean Permohonan Test',
            'latar_belakang' => 'Testing Antrean',
            'total_diminta' => 5000000,
            'status' => 'draft',
            'tgl_mulai' => now()->toDateString(),
            'tgl_selesai' => now()->addDays(2)->toDateString(),
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item 1',
            'volume' => 1,
            'satuan' => 'Paket',
            'harga_satuan' => 5000000,
            'jumlah_diminta' => 5000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);

        // Manager user harus melihat dokumen ini di antrean persetujuan saya
        Livewire::actingAs($this->managerUser)
            ->test(Dashboard::class)
            ->assertSee('Antrean Permohonan Test')
            ->assertSee($permohonan->fresh()->no_pengajuan);
    }

    public function test_laporan_monitoring_index_renders_and_exports_excel(): void
    {
        $this->createAndCompleteCycle();

        Excel::fake();

        Livewire::actingAs($this->adminUser)
            ->test(LaporanMonitoringIndex::class)
            ->assertOk()
            ->assertSee('Rekap Anggaran (RAB)')
            ->assertSee('Rekap Permohonan Dana')
            ->assertSee('Rekap PPD & UMP', false)
            ->assertSee('Realisasi Bulanan Matrix')
            ->call('exportRekapAnggaran')
            ->call('exportRekapPermohonan')
            ->call('exportRekapPpdUmp')
            ->call('exportRekapRealisasi');

        Excel::assertDownloaded("Rekap_Anggaran_{$this->ta->tahun}.xlsx");
        Excel::assertDownloaded("Rekap_Permohonan_{$this->ta->tahun}.xlsx");
        Excel::assertDownloaded("Rekap_PPD_dan_UMP_{$this->ta->tahun}.xlsx");
        Excel::assertDownloaded("Rekap_Realisasi_Bulanan_{$this->ta->tahun}.xlsx");
    }

    public function test_export_data_parity_with_dashboard(): void
    {
        $this->createAndCompleteCycle();

        // 1. Rekap Anggaran parity
        $anggaranExport = new RekapAnggaranExport($this->ta->id);
        $anggaranRows = $anggaranExport->collection();
        $rootRow = $anggaranRows->firstWhere('kode', '2');
        $this->assertNotNull($rootRow);
        $this->assertEquals(50000000, $rootRow['plafon_rab']);
        $this->assertEquals(10000000, $rootRow['realisasi']);
        $this->assertEquals(40000000, $rootRow['sisa_tersedia']);
        $this->assertEquals('20%', $rootRow['serapan']);

        // 2. Rekap Permohonan parity
        $permohonanExport = new RekapPermohonanExport($this->ta->id);
        $pRows = $permohonanExport->collection();
        $this->assertCount(1, $pRows);
        $this->assertEquals(10000000, $pRows->first()['nilai_diminta']);

        // 3. Rekap PPD & UMP parity
        $ppdUmpExport = new RekapPpdUmpExport($this->ta->id);
        $puRows = $ppdUmpExport->collection();
        $this->assertCount(1, $puRows);
        $this->assertEquals('PPD-DSH-001', $puRows->first()['no_ppd']);
        $this->assertEquals(10000000, $puRows->first()['nominal_ppd']);
        $this->assertEquals('Selesai', $puRows->first()['status_ump']);

        // 4. Rekap Realisasi Bulanan parity
        $realisasiExport = new RekapRealisasiBulananExport($this->ta->id);
        $rRows = $realisasiExport->collection();
        $rRoot = $rRows->firstWhere('kode', '2');
        $this->assertNotNull($rRoot);
        $this->assertEquals(10000000, $rRoot['total']);
    }

    public function test_pratinjau_data_anggaran_sorts_hierarchically_and_naturally(): void
    {
        // Buat pos anggaran dengan nomor multi digit untuk menguji natural sorting (1.1.1, 1.1.2, ..., 1.1.10)
        $parent = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '8.8',
            'nama' => 'Pos Parent Test Sorting',
            'depth' => 2,
            'plafon' => null,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $codes = ['8.8.10', '8.8.1', '8.8.2', '8.8.11', '8.8.3', '8.8.4', '8.8.5'];
        foreach ($codes as $code) {
            KodeAnggaran::create([
                'tahun_anggaran_id' => $this->ta->id,
                'parent_id' => $parent->id,
                'kode' => $code,
                'nama' => "Nama {$code}",
                'depth' => 3,
                'plafon' => 1000000,
                'budget_status' => 'disahkan',
                'is_active' => true,
            ]);
        }

        $component = Livewire::actingAs($this->adminUser)
            ->test(LaporanMonitoringIndex::class, ['tahunAnggaranId' => $this->ta->id]);

        $rows = collect($component->viewData('monitoringRows'))->pluck('kode')->values()->all();
        $filteredCodes = array_values(array_filter($rows, fn ($k) => str_starts_with($k, '8.8.')));

        // Urutan alami wajib: 8.8.1, 8.8.2, 8.8.3, 8.8.4, 8.8.5, 8.8.10, 8.8.11
        $expected = ['8.8.1', '8.8.2', '8.8.3', '8.8.4', '8.8.5', '8.8.10', '8.8.11'];
        $this->assertSame($expected, $filteredCodes);
    }
}
