<?php

namespace Tests\Feature;

use App\Exports\LaporanUmpPerOrangExport;
use App\Livewire\Laporan\LaporanUmpPerOrang;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpPenerima;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use App\Services\UmpWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanUmpPerOrangTest extends TestCase
{
    use RefreshDatabase;

    protected User $tuUser;

    protected User $managerUser;

    protected User $kadivUser;

    protected User $penerimaA;

    protected User $penerimaB;

    protected TahunAnggaran $ta;

    protected Unit $unitKlm;

    protected Unit $unitProgram;

    protected KodeAnggaran $leafNode;

    protected SumberDana $sumberDanaInfak;

    protected SumberDana $sumberDanaZakat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->tuUser = User::where('username', 'subakti')->firstOrFail();
        $this->managerUser = User::where('username', 'deden.salim')->firstOrFail();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $this->penerimaA = User::where('username', 'lias')->firstOrFail();
        $this->penerimaB = User::where('username', 'suryamin')->firstOrFail();

        $this->ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $this->unitProgram = Unit::where('kode', 'UNT-PRG-INT')->firstOrFail();
        $this->sumberDanaInfak = SumberDana::where('nama', 'like', '%Infak Umum%')->firstOrFail();
        $this->sumberDanaZakat = SumberDana::where('nama', 'like', '%Zakat%')->firstOrFail();

        $root = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '9',
            'nama' => 'Root Anggaran Rekap Laporan',
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

    /**
     * @param  array<int, int>  $penerimaIds
     */
    private function createUmpWithPenerima(
        User $pj,
        string $noPpd,
        string $kegiatan,
        int $jumlah,
        string $tglCair,
        array $penerimaIds = [],
        ?Unit $unit = null,
        ?SumberDana $sumberDana = null,
    ): Ump {
        $targetUnit = $unit ?? $this->unitKlm;
        $targetSumber = $sumberDana ?? $this->sumberDanaInfak;

        $permohonan = Permohonan::create([
            'no_pengajuan' => 'PMH-'.str_replace(['/', ' '], '-', $noPpd),
            'unit_id' => $targetUnit->id,
            'user_id' => $pj->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Pos Pengeluaran Kegiatan',
            'nama_kegiatan' => $kegiatan,
            'tgl_mulai' => $tglCair,
            'sumber_dana_id' => $targetSumber->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => $jumlah,
            'total_disetujui' => $jumlah,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => $kegiatan,
            'jumlah_diminta' => $jumlah,
            'jumlah_disetujui' => $jumlah,
        ]);

        $pencairan = (new PermohonanWorkflow)->cairkan($permohonan, $this->tuUser, [
            'no_ppd' => $noPpd,
            'tgl_cair' => $tglCair,
            'pj_id' => $pj->id,
            'penerima_ids' => $penerimaIds,
        ]);

        return $pencairan->ump()->firstOrFail();
    }

    public function test_route_and_page_render_successfully_with_sidebar_menu(): void
    {
        $this->actingAs($this->managerUser)
            ->get(route('laporan.laporan-per-orang'))
            ->assertOk()
            ->assertSee('Rekap Laporan per Orang')
            ->assertSee('Total Dokumen Laporan');
    }

    public function test_records_all_participants_across_multi_user_laporan_without_double_counting(): void
    {
        $umpWorkflow = new UmpWorkflow;

        // Laporan 1: dipakai 3 orang (Manager sebagai PJ + Penerima A + Penerima B; Penerima B belum isi baris item)
        $ump1 = $this->createUmpWithPenerima(
            pj: $this->managerUser,
            noPpd: 'PPD-UMP-001',
            kegiatan: 'Kegiatan Bersama Multi Penerima',
            jumlah: 5000000,
            tglCair: $this->ta->tahun.'-03-10',
            penerimaIds: [$this->penerimaA->id, $this->penerimaB->id],
        );

        $pivot1A = UmpPenerima::where('ump_id', $ump1->id)->where('user_id', $this->penerimaA->id)->firstOrFail();
        $umpWorkflow->saveItems($ump1, [
            ['tgl' => $this->ta->tahun.'-03-11', 'rincian' => 'Belanja PJ', 'no_bukti' => 'PJ-1', 'jumlah' => 1000000],
            ['tgl' => $this->ta->tahun.'-03-12', 'rincian' => 'Belanja Penerima A', 'no_bukti' => 'PA-1', 'jumlah' => 2500000, 'penerima_id' => $pivot1A->id],
        ]);

        // Laporan 2: PJ juga tercatat sebagai penerima di ump_penerima (harus tetap terhitung 1x laporan untuk orang tsb)
        $ump2 = $this->createUmpWithPenerima(
            pj: $this->kadivUser,
            noPpd: 'PPD-UMP-002',
            kegiatan: 'Kegiatan Kadiv dan Penerima A',
            jumlah: 4000000,
            tglCair: $this->ta->tahun.'-04-05',
            penerimaIds: [$this->kadivUser->id, $this->penerimaA->id],
        );

        $pivot2Kadiv = UmpPenerima::where('ump_id', $ump2->id)->where('user_id', $this->kadivUser->id)->firstOrFail();
        $pivot2A = UmpPenerima::where('ump_id', $ump2->id)->where('user_id', $this->penerimaA->id)->firstOrFail();
        $umpWorkflow->saveItems($ump2, [
            ['tgl' => $this->ta->tahun.'-04-06', 'rincian' => 'Belanja PJ Kadiv', 'no_bukti' => 'KD-1', 'jumlah' => 500000],
            ['tgl' => $this->ta->tahun.'-04-07', 'rincian' => 'Belanja Penerima Kadiv', 'no_bukti' => 'KD-2', 'jumlah' => 700000, 'penerima_id' => $pivot2Kadiv->id],
            ['tgl' => $this->ta->tahun.'-04-08', 'rincian' => 'Belanja Penerima A ke-2', 'no_bukti' => 'PA-2', 'jumlah' => 1500000, 'penerima_id' => $pivot2A->id],
        ]);

        // Laporan 3 yang di-void tidak boleh ikut terhitung
        $umpVoid = $this->createUmpWithPenerima(
            pj: $this->managerUser,
            noPpd: 'PPD-UMP-VOID',
            kegiatan: 'Kegiatan Void',
            jumlah: 9000000,
            tglCair: $this->ta->tahun.'-04-15',
            penerimaIds: [$this->penerimaA->id],
        );
        $umpVoid->update(['status' => 'dibatalkan']);

        // Total penggunaan sah = 1.000.000 + 2.500.000 + 500.000 + 700.000 + 1.500.000 = 6.200.000
        // Penerima A: 2x Laporan (2.500.000 + 1.500.000 = 4.000.000)
        // Kadiv: 1x Laporan sebagai PJ & Penerima (500.000 + 700.000 = 1.200.000)
        // Manager: 1x Laporan sebagai PJ (1.000.000)
        // Penerima B: 1x Laporan sebagai Penerima (0)
        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->assertViewHas('totalLaporan', 2)
            ->assertViewHas('totalOrang', 4)
            ->assertViewHas('totalPenggunaan', 6200000)
            ->assertSee($this->penerimaA->name)
            ->assertSee('2x Laporan')
            ->assertSee('4.000.000')
            ->assertSee($this->kadivUser->name)
            ->assertSee('1.200.000')
            ->assertSee($this->managerUser->name)
            ->assertSee('1.000.000')
            ->assertSee($this->penerimaB->name)
            ->assertDontSee('Kegiatan Void')
            ->call('openUserDetail', $this->penerimaA->id)
            ->assertSee('PPD-UMP-001')
            ->assertSee('PPD-UMP-002')
            ->assertSee($this->managerUser->name)
            ->call('closeUserDetail')
            ->call('setTab', 'laporan')
            ->assertSee('PPD-UMP-001')
            ->assertSee('Kegiatan Bersama Multi Penerima')
            ->assertSee($this->penerimaB->name);
    }

    public function test_filters_by_search_month_unit_sumber_dana_and_status(): void
    {
        $umpWorkflow = new UmpWorkflow;

        $umpMaret = $this->createUmpWithPenerima(
            pj: $this->managerUser,
            noPpd: 'PPD-FLT-001',
            kegiatan: 'Kegiatan Zakat Maret',
            jumlah: 2000000,
            tglCair: $this->ta->tahun.'-03-05',
            penerimaIds: [$this->penerimaA->id],
            unit: $this->unitKlm,
            sumberDana: $this->sumberDanaZakat,
        );
        $pivotA = UmpPenerima::where('ump_id', $umpMaret->id)->where('user_id', $this->penerimaA->id)->firstOrFail();
        $umpWorkflow->saveItems($umpMaret, [
            ['tgl' => $this->ta->tahun.'-03-06', 'rincian' => 'Belanja Zakat', 'no_bukti' => 'Z-1', 'jumlah' => 1500000, 'penerima_id' => $pivotA->id],
        ]);
        $umpMaret->update(['status' => 'selesai']);

        $umpApril = $this->createUmpWithPenerima(
            pj: $this->kadivUser,
            noPpd: 'PPD-FLT-002',
            kegiatan: 'Kegiatan Infak April',
            jumlah: 3000000,
            tglCair: $this->ta->tahun.'-04-10',
            penerimaIds: [$this->penerimaB->id],
            unit: $this->unitProgram,
            sumberDana: $this->sumberDanaInfak,
        );
        $pivotB = UmpPenerima::where('ump_id', $umpApril->id)->where('user_id', $this->penerimaB->id)->firstOrFail();
        $umpWorkflow->saveItems($umpApril, [
            ['tgl' => $this->ta->tahun.'-04-11', 'rincian' => 'Belanja Infak', 'no_bukti' => 'I-1', 'jumlah' => 2100000, 'penerima_id' => $pivotB->id],
        ]);

        // Pencarian nama penerima A hanya menampilkan Penerima A pada tab rekap
        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->set('search', $this->penerimaA->name)
            ->assertSee($this->penerimaA->name)
            ->assertDontSee($this->managerUser->name)
            ->assertDontSee($this->penerimaB->name);

        // Filter bulan 3 -> hanya laporan Maret
        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->set('bulanFilter', '3')
            ->assertSee($this->penerimaA->name)
            ->assertDontSee($this->penerimaB->name);

        // Filter unit Program -> hanya laporan unit Program
        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->set('unitFilter', (string) $this->unitProgram->id)
            ->assertSee($this->penerimaB->name)
            ->assertDontSee($this->penerimaA->name);

        // Filter sumber dana Zakat -> hanya laporan Zakat
        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->set('sumberDanaFilter', (string) $this->sumberDanaZakat->id)
            ->assertSee($this->penerimaA->name)
            ->assertDontSee($this->penerimaB->name);

        // Filter status Selesai -> hanya laporan selesai
        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->set('statusFilter', 'selesai')
            ->assertSee($this->penerimaA->name)
            ->assertDontSee($this->penerimaB->name);
    }

    public function test_export_excel_downloads_and_contains_multi_person_rows(): void
    {
        $ump = $this->createUmpWithPenerima(
            pj: $this->managerUser,
            noPpd: 'PPD-EXP-001',
            kegiatan: 'Kegiatan Export Laporan',
            jumlah: 3000000,
            tglCair: $this->ta->tahun.'-05-01',
            penerimaIds: [$this->penerimaA->id],
        );

        $pivotA = UmpPenerima::where('ump_id', $ump->id)->where('user_id', $this->penerimaA->id)->firstOrFail();
        (new UmpWorkflow)->saveItems($ump, [
            ['tgl' => $this->ta->tahun.'-05-02', 'rincian' => 'Item PJ', 'no_bukti' => 'E-1', 'jumlah' => 800000],
            ['tgl' => $this->ta->tahun.'-05-03', 'rincian' => 'Item Penerima', 'no_bukti' => 'E-2', 'jumlah' => 1700000, 'penerima_id' => $pivotA->id],
        ]);

        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->call('exportExcel')
            ->assertFileDownloaded('Rekap_Laporan_Per_Orang_'.$this->ta->tahun.'.xlsx');

        $rows = (new LaporanUmpPerOrangExport(tahunAnggaranId: $this->ta->id))->collection();
        $this->assertCount(2, $rows);

        $rowA = $rows->firstWhere('nama', $this->penerimaA->name);
        $rowPj = $rows->firstWhere('nama', $this->managerUser->name);

        $this->assertNotNull($rowA);
        $this->assertSame(1700000, $rowA['total_penggunaan']);
        $this->assertSame(1, $rowA['frekuensi_laporan']);

        $this->assertNotNull($rowPj);
        $this->assertSame(800000, $rowPj['total_penggunaan']);
        $this->assertSame(1, $rowPj['frekuensi_laporan']);
    }

    public function test_follows_global_year_switcher_and_dispatches_event(): void
    {
        $ta2025 = TahunAnggaran::create([
            'tahun' => 2025,
            'status' => 'ditutup',
            'deskripsi' => 'Tahun 2025',
        ]);

        Livewire::actingAs($this->managerUser)
            ->test(LaporanUmpPerOrang::class)
            ->assertSet('tahunAnggaranId', $this->ta->id)
            ->dispatch('tahun-anggaran-changed', tahunAnggaranId: $ta2025->id)
            ->assertSet('tahunAnggaranId', $ta2025->id)
            ->assertSet('tahunFilter', '');
    }
}
