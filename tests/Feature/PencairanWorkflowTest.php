<?php

namespace Tests\Feature;

use App\Livewire\Pencairan\PencairanIndex;
use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Models\Asnaf;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\Setting;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Tests\TestCase;

class PencairanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;

    protected User $petugasUser;

    protected User $plainUser;

    protected User $managerUser;

    protected Permohonan $permohonan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->petugasUser = User::where('username', 'subakti')->firstOrFail();
        $this->plainUser = User::where('username', 'rudiansah')->firstOrFail();
        $this->managerUser = User::where('username', 'deden.salim')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '98',
            'nama' => 'Tes Pencairan PPD',
            'depth' => 1,
            'plafon' => 20000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9101',
            'unit_id' => $unit->id,
            'user_id' => $this->petugasUser->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Santunan Yatim Piatu',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $this->permohonan->id,
            'uraian' => 'Paket sembako dan uang santunan',
            'jumlah_diminta' => 5000000,
            'jumlah_disetujui' => 5000000,
        ]);
    }

    public function test_cairkan_success_creates_pencairan_and_ump(): void
    {
        $workflow = new PermohonanWorkflow;

        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0001',
            'tgl_cair' => '2026-10-16',
        ]);

        $this->assertDatabaseHas('pencairan', [
            'permohonan_id' => $this->permohonan->id,
            'no_ppd' => 'PPD/KEU/2026/0001',
            'jumlah' => 5000000,
            'status' => 'dicairkan',
        ]);

        $this->assertSame('dicairkan', $this->permohonan->fresh()->status);
        $this->assertSame(5000000, $pencairan->jumlah);
        $this->assertSame($this->financeUser->id, $pencairan->fresh()->dicairkan_oleh);

        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        $this->assertSame('draft', $ump->status);
        $this->assertSame(0, $ump->total_penggunaan);
        $this->assertSame(5000000, $ump->saldo);
        $this->assertSame(5000000, $ump->jumlah_total);
        $this->assertSame($this->managerUser->id, $ump->pj_id);
        $this->assertSame('Santunan Yatim Piatu', $ump->tujuan);

        // jatuh_tempo = tgl_cair + ump.tenggat_hari (default 14 hari)
        $tenggat = (int) Setting::get('ump.tenggat_hari', 14);
        $expectedDue = Carbon::parse('2026-10-16')->addDays($tenggat)->toDateString();
        $this->assertSame($expectedDue, $ump->jatuh_tempo->toDateString());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permohonan.cairkan',
            'entity_id' => $this->permohonan->id,
        ]);
    }

    public function test_cairkan_by_petugas_input_records_the_issuer(): void
    {
        $workflow = new PermohonanWorkflow;

        $pencairan = $workflow->cairkan($this->permohonan, $this->petugasUser, [
            'no_ppd' => 'PPD/KEU/2026/0002',
            'tgl_cair' => '2026-10-16',
        ]);

        $this->assertDatabaseHas('pencairan', [
            'id' => $pencairan->id,
            'dicairkan_oleh' => $this->petugasUser->id,
        ]);
    }

    public function test_cairkan_duplicate_no_ppd_fails(): void
    {
        $workflow = new PermohonanWorkflow;

        $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0001',
            'tgl_cair' => '2026-10-16',
        ]);

        $other = $this->makePermohonan('KLM-PMH-2026-9102');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah dipakai');

        $workflow->cairkan($other, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0001',
            'tgl_cair' => '2026-10-16',
        ]);
    }

    public function test_cairkan_wrong_status_fails(): void
    {
        $this->permohonan->update(['status' => 'diketahui_kadiv']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Disetujui Keuangan');

        (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0003',
            'tgl_cair' => '2026-10-16',
        ]);
    }

    public function test_cairkan_without_authorization_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hak untuk mencairkan dana');

        (new PermohonanWorkflow)->cairkan($this->permohonan, $this->plainUser, [
            'no_ppd' => 'PPD/KEU/2026/0004',
            'tgl_cair' => '2026-10-16',
        ]);
    }

    public function test_cairkan_without_no_ppd_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nomor PPD wajib diisi');

        (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => '   ',
            'tgl_cair' => '2026-10-16',
        ]);
    }

    public function test_print_ppd_pdf_returns_pdf(): void
    {
        $pencairan = (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0005',
            'tgl_cair' => '2026-10-16',
        ]);

        $response = $this->actingAs($this->financeUser)->get("/pencairan/{$pencairan->id}/cetak-pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_print_ppd_pdf_requires_auth(): void
    {
        $pencairan = (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0006',
            'tgl_cair' => '2026-10-16',
        ]);

        $this->get("/pencairan/{$pencairan->id}/cetak-pdf")->assertRedirect(route('login'));
    }

    public function test_cairkan_with_custom_pj_id_sets_ump_pj(): void
    {
        $workflow = new PermohonanWorkflow;
        $customPj = User::where('username', 'sigit.nugroho')->firstOrFail();

        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/PJ-TEST',
            'tgl_cair' => '2026-10-16',
            'pj_id' => $customPj->id,
        ]);

        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        $this->assertSame($customPj->id, $ump->pj_id);
    }

    public function test_cairkan_with_non_manager_pj_falls_back_to_unit_manager(): void
    {
        $workflow = new PermohonanWorkflow;
        $staffUser = User::where('username', 'rudiansah')->firstOrFail(); // urutan 1

        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/PJ-STAFF-TEST',
            'tgl_cair' => '2026-10-16',
            'pj_id' => $staffUser->id,
        ]);

        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        $this->assertSame($this->managerUser->id, $ump->pj_id);
    }

    public function test_cairkan_without_pj_id_defaults_to_unit_manager(): void
    {
        $workflow = new PermohonanWorkflow;
        $p2 = $this->makePermohonan('KLM-PMH-2026-9102');

        $pencairan = $workflow->cairkan($p2, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/NO-PJ',
            'tgl_cair' => '2026-10-16',
        ]);

        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        $this->assertSame($this->managerUser->id, $ump->pj_id);
    }

    public function test_batalkan_pencairan_by_finance_cancels_pencairan_and_ump(): void
    {
        $workflow = new PermohonanWorkflow;
        $pencairan = $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/BATAL-TEST',
            'tgl_cair' => '2026-10-16',
        ]);

        $batal = $workflow->batalkanPencairan($pencairan, $this->financeUser, 'Salah input nomor dan rekening');

        $this->assertEquals('dibatalkan', $batal->status);
        $this->assertEquals('Salah input nomor dan rekening', $batal->alasan_batal);
        $this->assertEquals('dibatalkan', $batal->ump->fresh()->status);
        $this->assertEquals('disetujui_keuangan', $this->permohonan->fresh()->status);
    }

    public function test_pencairan_index_renders_and_supports_search(): void
    {
        $workflow = new PermohonanWorkflow;
        $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/INDEX-TEST',
            'tgl_cair' => '2026-10-16',
        ]);

        Livewire::actingAs($this->financeUser)
            ->test(PencairanIndex::class)
            ->assertOk()
            ->assertSee('PPD/KEU/2026/INDEX-TEST')
            ->assertSee('Total Dana Dicairkan')
            ->set('search', 'NONEXISTENT')
            ->assertSee('Tidak ada data pencairan PPD');
    }

    public function test_pencairan_index_filters_by_bulan_tahun_sumber_dana_and_exports_excel(): void
    {
        $workflow = new PermohonanWorkflow;
        $workflow->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/202610/0001',
            'tgl_cair' => '2026-10-15',
        ]);

        Excel::fake();

        Livewire::actingAs($this->financeUser)
            ->test(PencairanIndex::class)
            ->assertOk()
            ->assertSee('PPD/202610/0001')
            ->set('tahunFilter', '2026')
            ->set('bulanFilter', '10')
            ->assertSee('PPD/202610/0001')
            ->set('bulanFilter', '11')
            ->assertDontSee('PPD/202610/0001')
            ->set('bulanFilter', '')
            ->set('sumberDanaFilter', (string) $this->permohonan->sumber_dana_id)
            ->assertSee('PPD/202610/0001')
            ->call('exportExcel');

        Excel::assertDownloaded('Rekap_PPD_dan_Laporan_2026.xlsx');
    }

    public function test_cairkan_modal_suggests_4_angka_tahun_dan_bulan_and_allows_custom(): void
    {
        $component = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $this->permohonan->id])
            ->call('openCairkanModal');

        $suggestedNoPpd = $component->get('noPpd');
        $year = date('Y');
        $month = date('m');
        $this->assertMatchesRegularExpression("/^PPD\/{$year}{$month}\/\d{4}$/", $suggestedNoPpd);
        $this->assertSame($this->managerUser->id, $component->get('pjId'));
        // Allow custom input as required by Gambaran & Flow line 48
        $component->set('noPpd', 'PPD-CUSTOM-2026-9999')
            ->call('submitCairkan');

        $this->assertDatabaseHas('pencairan', [
            'permohonan_id' => $this->permohonan->id,
            'no_ppd' => 'PPD-CUSTOM-2026-9999',
        ]);

        $this->assertDatabaseHas('ump', [
            'tujuan' => $this->permohonan->nama_kegiatan,
        ]);
    }

    public function test_cairkan_modal_auto_selects_manager_for_humas_and_diklat(): void
    {
        $unitHum = Unit::where('kode', 'UNT-SEK-HUM')->firstOrFail();
        $unitDkl = Unit::where('kode', 'UNT-SEK-DKL')->firstOrFail();
        $mgrHum = $unitHum->getManager();
        $mgrDkl = $unitDkl->getManager();

        $this->assertNotNull($mgrHum);
        $this->assertNotNull($mgrDkl);

        // Permohonan Humas -> Manager Humas
        $pHum = $this->makePermohonan('HUM-PMH-2026-0001');
        $pHum->unit_id = $unitHum->id;
        $pHum->save();

        $compHum = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $pHum->id])
            ->call('openCairkanModal');
        $this->assertSame($mgrHum->id, $compHum->get('pjId'));

        // Permohonan Diklat -> Manager Diklat
        $pDkl = $this->makePermohonan('DKL-PMH-2026-0001');
        $pDkl->unit_id = $unitDkl->id;
        $pDkl->save();

        $compDkl = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $pDkl->id])
            ->call('openCairkanModal');
        $this->assertSame($mgrDkl->id, $compDkl->get('pjId'));
    }

    public function test_permohonan_create_auto_selects_infak_umum_for_humas_and_amil_for_other(): void
    {
        $unitHumas = Unit::where('kode', 'UNT-SEK-HUM')->firstOrFail();
        $unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $infakUmum = SumberDana::where('nama', 'like', '%Infak Umum%')->firstOrFail();
        $zakat = SumberDana::where('nama', 'like', '%Zakat%')->firstOrFail();
        $asnafAmil = Asnaf::where('nama', 'Amil')->firstOrFail();

        // Humas -> Infak Umum
        Livewire::actingAs($this->financeUser)
            ->test(PermohonanCreate::class)
            ->set('unit_id', $unitHumas->id)
            ->assertSet('sumber_dana_id', $infakUmum->id);

        // Non-Humas (selain Humas) -> Amil / Zakat
        Livewire::actingAs($this->financeUser)
            ->test(PermohonanCreate::class)
            ->set('unit_id', $unitKlm->id)
            ->assertSet('sumber_dana_id', $zakat->id)
            ->call('addItem')
            ->assertCount('items', 2);
    }

    public function test_cairkan_modal_and_workflow_auto_select_kadiv_when_permohonan_owned_by_kadiv(): void
    {
        $kadivSek = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $divSek = Unit::where('kode', 'DIV-SEK')->firstOrFail();
        $unitHum = Unit::where('kode', 'UNT-SEK-HUM')->firstOrFail();

        // 1. Kadiv mengajukan dengan unit Divisi (DIV-SEK) -> otomatis PJ = Kadiv (bukan Manager unit anak)
        $pDiv = $this->makePermohonan('SEK-PMH-2026-KADIV-1');
        $pDiv->update([
            'unit_id' => $divSek->id,
            'user_id' => $kadivSek->id,
        ]);

        $compDiv = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $pDiv->id])
            ->call('openCairkanModal');

        $this->assertSame($kadivSek->id, $compDiv->get('pjId'));
        $candidatesDiv = $compDiv->viewData('pjCandidates');
        $this->assertSame($kadivSek->id, $candidatesDiv[0]['id']);

        // 2. Kadiv mengajukan pada unit anak (UNT-SEK-HUM) -> tetap otomatis PJ = Kadiv (bukan Sigit Nugroho)
        $pChild = $this->makePermohonan('HUM-PMH-2026-KADIV-2');
        $pChild->update([
            'unit_id' => $unitHum->id,
            'user_id' => $kadivSek->id,
        ]);

        $compChild = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $pChild->id])
            ->call('openCairkanModal');

        $this->assertSame($kadivSek->id, $compChild->get('pjId'));
        $candidatesChild = $compChild->viewData('pjCandidates');
        $this->assertSame($kadivSek->id, $candidatesChild[0]['id']);

        // 3. Cairkan via workflow tanpa pj_id eksplisit -> PJ UMP otomatis Kadiv
        $pencairan = (new PermohonanWorkflow)->cairkan($pChild, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/KADIV-AUTO',
            'tgl_cair' => '2026-11-24',
        ]);

        $ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
        $this->assertSame($kadivSek->id, $ump->pj_id);

        // 4. PDF PPD menampilkan header Kepala Divisi
        $html = view('pdf.pencairan', [
            'pencairan' => $pencairan->fresh(['permohonan.unit', 'permohonan.user', 'permohonan.kodeAnggaran', 'permohonan.sumberDana', 'permohonan.items', 'permohonan.approvals', 'ump.pj.leveling']),
            'permohonan' => $pChild->fresh(),
            'logoBase64' => null,
        ])->render();
        $this->assertStringContainsString('PENANGGUNG JAWAB (PJ / KEPALA DIVISI)', $html);
    }

    public function test_cairkan_modal_and_workflow_auto_select_manager_for_supervisor_and_manager(): void
    {
        $unitHum = Unit::where('kode', 'UNT-SEK-HUM')->firstOrFail();
        $spvIt = User::where('username', 'rudiansah')->firstOrFail();
        $mgrHum = User::where('username', 'sigit.nugroho')->firstOrFail();

        // 1. Permohonan Supervisor (Rudiansah) -> otomatis PJ = Manager unit (Sigit Nugroho)
        $pSpv = $this->makePermohonan('HUM-PMH-2026-SPV-1');
        $pSpv->update([
            'unit_id' => $unitHum->id,
            'user_id' => $spvIt->id,
        ]);

        $compSpv = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $pSpv->id])
            ->call('openCairkanModal');
        $this->assertSame($mgrHum->id, $compSpv->get('pjId'));

        // 2. Permohonan Manager Pengeluaran di DIV-KEU -> otomatis PJ = Manager Pengeluaran itu sendiri
        $divKeu = Unit::where('kode', 'DIV-KEU')->firstOrFail();
        $mgrKeluar = User::where('username', 'manager.keuangan.keluar')->firstOrFail();

        $pMgr = $this->makePermohonan('KEU-PMH-2026-MGR-1');
        $pMgr->update([
            'unit_id' => $divKeu->id,
            'user_id' => $mgrKeluar->id,
        ]);

        $compMgr = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $pMgr->id])
            ->call('openCairkanModal');
        $this->assertSame($mgrKeluar->id, $compMgr->get('pjId'));

        $pencairan = (new PermohonanWorkflow)->cairkan($pMgr, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/MGR-AUTO',
            'tgl_cair' => '2026-11-24',
        ]);
        $this->assertSame($mgrKeluar->id, $pencairan->ump->pj_id);
    }

    protected function makePermohonan(string $noPengajuan): Permohonan
    {
        return Permohonan::create([
            'no_pengajuan' => $noPengajuan,
            'unit_id' => $this->permohonan->unit_id,
            'user_id' => $this->permohonan->user_id,
            'kode_anggaran_id' => $this->permohonan->kode_anggaran_id,
            'program' => $this->permohonan->program,
            'nama_kegiatan' => $this->permohonan->nama_kegiatan,
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->permohonan->sumber_dana_id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 3000000,
            'total_disetujui' => 3000000,
        ]);
    }
}
