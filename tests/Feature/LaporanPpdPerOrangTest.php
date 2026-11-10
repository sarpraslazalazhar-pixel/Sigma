<?php

namespace Tests\Feature;

use App\Livewire\Laporan\LaporanPpdPerOrang;
use App\Models\KodeAnggaran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanPpdPerOrangTest extends TestCase
{
    use RefreshDatabase;

    protected User $managerUser;

    protected User $kadivUser;

    protected TahunAnggaran $ta;

    protected Unit $unitKlm;

    protected KodeAnggaran $leafNode;

    protected SumberDana $sumberDanaInfak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->managerUser = User::where('username', 'deden.salim')->firstOrFail();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();

        $this->ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $this->sumberDanaInfak = SumberDana::where('nama', 'like', '%Infak Umum%')->firstOrFail();

        $root = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '9',
            'nama' => 'Root Anggaran Laporan',
            'depth' => 1,
            'plafon' => 100000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $root->id,
            'kode' => '9.1',
            'nama' => 'Pos Pengeluaran Kegiatan',
            'depth' => 2,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    public function test_renders_laporan_ppd_per_orang_successfully(): void
    {
        Livewire::actingAs($this->managerUser)
            ->test(LaporanPpdPerOrang::class)
            ->assertStatus(200)
            ->assertSee('Rekap PPD per Orang')
            ->assertSee('Total PPD Dicairkan');
    }

    public function test_counts_ppd_frequency_and_nominal_correctly(): void
    {
        // 1. Manager punya 2 pencairan PPD
        $p1 = Permohonan::create([
            'no_pengajuan' => 'PMH-MGR-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Pengeluaran Kegiatan',
            'nama_kegiatan' => 'Kegiatan Manager 1',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'dicairkan',
            'total_diminta' => 3000000,
            'total_disetujui' => 3000000,
        ]);
        Pencairan::create([
            'permohonan_id' => $p1->id,
            'no_ppd' => 'PPD-2026-0001',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 3000000,
            'status' => 'dicairkan',
        ]);

        $p2 = Permohonan::create([
            'no_pengajuan' => 'PMH-MGR-002',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Pengeluaran Kegiatan',
            'nama_kegiatan' => 'Kegiatan Manager 2',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'dicairkan',
            'total_diminta' => 4000000,
            'total_disetujui' => 4000000,
        ]);
        Pencairan::create([
            'permohonan_id' => $p2->id,
            'no_ppd' => 'PPD-2026-0002',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 4000000,
            'status' => 'dicairkan',
        ]);

        // 2. Kadiv punya 1 pencairan PPD
        $p3 = Permohonan::create([
            'no_pengajuan' => 'PMH-KDV-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Pengeluaran Kegiatan',
            'nama_kegiatan' => 'Kegiatan Kadiv 1',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'dicairkan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);
        Pencairan::create([
            'permohonan_id' => $p3->id,
            'no_ppd' => 'PPD-2026-0003',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 5000000,
            'status' => 'dicairkan',
        ]);

        Livewire::actingAs($this->managerUser)
            ->test(LaporanPpdPerOrang::class)
            ->assertSee($this->managerUser->name)
            ->assertSee('2x PPD')
            ->assertSee('7.000.000')
            ->assertSee($this->kadivUser->name)
            ->assertSee('1x PPD')
            ->assertSee('5.000.000');
    }

    public function test_filters_by_month_and_sumber_dana_and_unit(): void
    {
        $zakat = SumberDana::where('nama', 'like', '%Zakat%')->firstOrFail();

        $p1 = Permohonan::create([
            'no_pengajuan' => 'PMH-FLT-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Pengeluaran Kegiatan',
            'nama_kegiatan' => 'Kegiatan Zakat Bulan 3',
            'tgl_mulai' => '2026-03-01',
            'sumber_dana_id' => $zakat->id,
            'status' => 'dicairkan',
            'total_diminta' => 2000000,
            'total_disetujui' => 2000000,
        ]);
        Pencairan::create([
            'permohonan_id' => $p1->id,
            'no_ppd' => 'PPD-2026-0010',
            'tgl_cair' => '2026-03-05',
            'jumlah' => 2000000,
            'status' => 'dicairkan',
        ]);

        $p2 = Permohonan::create([
            'no_pengajuan' => 'PMH-FLT-002',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Pengeluaran Kegiatan',
            'nama_kegiatan' => 'Kegiatan Infak Bulan 4',
            'tgl_mulai' => '2026-04-01',
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'dicairkan',
            'total_diminta' => 3000000,
            'total_disetujui' => 3000000,
        ]);
        Pencairan::create([
            'permohonan_id' => $p2->id,
            'no_ppd' => 'PPD-2026-0020',
            'tgl_cair' => '2026-04-10',
            'jumlah' => 3000000,
            'status' => 'dicairkan',
        ]);

        // Filter bulan 3 (Maret) -> hanya Manager yang tampil
        Livewire::actingAs($this->managerUser)
            ->test(LaporanPpdPerOrang::class)
            ->set('bulanFilter', '3')
            ->assertSee($this->managerUser->name)
            ->assertDontSee($this->kadivUser->name);

        // Filter sumber dana Zakat -> hanya Manager yang tampil
        Livewire::actingAs($this->managerUser)
            ->test(LaporanPpdPerOrang::class)
            ->set('sumberDanaFilter', (string) $zakat->id)
            ->assertSee($this->managerUser->name)
            ->assertDontSee($this->kadivUser->name);
    }

    public function test_export_excel_downloads_valid_file(): void
    {
        Livewire::actingAs($this->managerUser)
            ->test(LaporanPpdPerOrang::class)
            ->call('exportExcel')
            ->assertFileDownloaded('Rekap_PPD_Per_Orang_'.$this->ta->tahun.'.xlsx');
    }

    public function test_follows_global_year_switcher_and_dispatches_event(): void
    {
        $ta2025 = TahunAnggaran::create([
            'tahun' => 2025,
            'status' => 'ditutup',
            'deskripsi' => 'Tahun 2025',
        ]);

        $test = Livewire::actingAs($this->managerUser)
            ->test(LaporanPpdPerOrang::class)
            ->assertSet('tahunAnggaranId', $this->ta->id);

        // Simulasi event perubahan tahun anggaran dari navbar YearSwitcher
        $test->dispatch('tahun-anggaran-changed', tahunAnggaranId: $ta2025->id)
            ->assertSet('tahunAnggaranId', $ta2025->id)
            ->assertSet('tahunFilter', '');
    }
}
