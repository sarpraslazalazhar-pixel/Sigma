<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Livewire\Permohonan\PermohonanEdit;
use App\Livewire\Permohonan\PermohonanIndex;
use App\Models\Asnaf;
use App\Models\KodeAnggaran;
use App\Models\Leveling;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\ProgramUtama;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class PermohonanUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected User $managerUser;

    protected User $kadivUser;

    protected User $financeUser;

    protected TahunAnggaran $ta;

    protected Unit $unitSek;

    protected Unit $unitKlm;

    protected SumberDana $sumberDanaZakat;

    protected SumberDana $sumberDanaInfak;

    protected KodeAnggaran $leafNode;

    protected ProgramUtama $programUtama;

    protected Asnaf $asnafFakir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->staffUser = User::where('username', 'subakti')->firstOrFail();
        $this->managerUser = User::where('username', 'deden.salim')->firstOrFail();
        $this->kadivUser = User::where('username', 'rahmatullah.sidik')->firstOrFail();
        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();

        $this->ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $this->unitSek = Unit::where('kode', 'UNT-SEK-DKL')->firstOrFail();
        $this->unitKlm = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();

        $this->sumberDanaZakat = SumberDana::where('nama', 'Zakat')->firstOrFail();
        $this->sumberDanaInfak = SumberDana::where('nama', 'Infak Umum')->firstOrFail();

        $this->programUtama = ProgramUtama::firstOrFail();
        $this->asnafFakir = Asnaf::firstOrFail();

        $root = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'kode' => '5',
            'nama' => 'Beban Operasional Tes',
            'depth' => 1,
            'plafon' => 100000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->leafNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->ta->id,
            'parent_id' => $root->id,
            'kode' => '5.1',
            'nama' => 'Biaya Kegiatan Sosial Fakir',
            'depth' => 2,
            'plafon' => 50000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
    }

    public function test_can_render_permohonan_index_and_create_pages(): void
    {
        $this->actingAs($this->staffUser)
            ->get('/permohonan')
            ->assertStatus(200)
            ->assertSee('Permohonan Dana')
            ->assertSee('Buat Permohonan Baru');

        $this->actingAs($this->staffUser)
            ->get('/permohonan/buat')
            ->assertStatus(200)
            ->assertSee('Buat Permohonan Dana')
            ->assertSee('Informasi Pengajuan')
            ->assertSee('Pengajuan oleh Petugas TU');
    }

    public function test_validation_submit_multi_row_items(): void
    {
        // Gagal karena uraian dan nominal kosong
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('program', 'Program Sosial')
            ->set('nama_kegiatan', 'Kegiatan Tanpa Rincian')
            ->set('user_id', $this->managerUser->id)
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items', [
                ['uraian' => '', 'jumlah_diminta' => '', 'asnaf_id' => null, 'program_utama_id' => null],
            ])
            ->call('submitNow')
            ->assertHasErrors(['items.0.uraian', 'items.0.jumlah_diminta']);

        // Gagal karena sumber dana Zakat wajib Asnaf
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('program', 'Program Zakat')
            ->set('nama_kegiatan', 'Penyaluran Zakat')
            ->set('user_id', $this->managerUser->id)
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaZakat->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items', [
                ['uraian' => 'Bantuan Sembako', 'jumlah_diminta' => 1000000, 'asnaf_id' => null, 'program_utama_id' => null],
            ])
            ->call('submitNow')
            ->assertHasErrors(['items.0.asnaf_id']);
    }

    public function test_can_save_draft_and_submit_now(): void
    {
        // 1. Simpan Draft
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('program', 'Program Pendidikan')
            ->set('nama_kegiatan', 'Beasiswa Prestasi Dhuafa')
            ->set('user_id', $this->managerUser->id)
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaZakat->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items', [
                [
                    'uraian' => 'Tahap 1 Uang SPP',
                    'asnaf_id' => $this->asnafFakir->id,
                    'program_utama_id' => null,
                    'jumlah_diminta' => 2500000,
                ],
                [
                    'uraian' => 'Tahap 2 Pembelian Buku',
                    'asnaf_id' => $this->asnafFakir->id,
                    'program_utama_id' => null,
                    'jumlah_diminta' => 1500000,
                ],
            ])
            ->call('saveDraft')
            ->assertHasNoErrors();

        $draft = Permohonan::where('nama_kegiatan', 'Beasiswa Prestasi Dhuafa')->firstOrFail();
        $this->assertEquals('draft', $draft->status);
        $this->assertEquals(4000000, $draft->total_diminta);
        $this->assertEquals(2, $draft->items()->count());

        // 2. Submit Sekarang
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('program', 'Program Dakwah')
            ->set('nama_kegiatan', 'Kajian Dhuha Spesial')
            ->set('user_id', $this->managerUser->id)
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items', [
                [
                    'uraian' => 'Honor Narasumber & Konsumsi',
                    'asnaf_id' => null,
                    'program_utama_id' => null,
                    'jumlah_diminta' => 5000000,
                ],
            ])
            ->call('submitNow')
            ->assertHasNoErrors();

        $submitted = Permohonan::where('nama_kegiatan', 'Kajian Dhuha Spesial')->firstOrFail();
        $this->assertEquals('diajukan', $submitted->status);
        $this->assertNotEmpty($submitted->no_pengajuan);
        $this->assertStringContainsString('-PMH-', $submitted->no_pengajuan);
    }

    public function test_selecting_kode_anggaran_automatically_sets_program_to_budget_name(): void
    {
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->assertSet('program', $this->leafNode->nama);
    }

    public function test_render_detail_and_approval_actions(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-TEST-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Santunan Rutin Yatim',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Paket Sembako',
            'jumlah_diminta' => 8000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Akses halaman detail oleh Kepala Divisi
        $this->actingAs($this->kadivUser)
            ->get("/permohonan/{$permohonan->id}")
            ->assertStatus(200)
            ->assertSee('Santunan Rutin Yatim')
            ->assertSee('Setujui Dokumen');

        // Tahap 1 (Manajer -> Kadiv Divisi): dicatat otomatis atas nama approver login
        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->call('openAction', 'setuju')
            ->set('actionNotes', 'Diperiksa Kepala Divisi')
            ->call('submitAction')
            ->assertHasNoErrors()
            ->assertSet('showActionModal', false);

        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $permohonan->id,
            'tahap' => 'kadiv',
            'keputusan' => 'setuju',
            'approver_id' => $this->kadivUser->id,
            'signer_name' => $this->kadivUser->name,
        ]);
    }

    public function test_revision_and_reject_require_action_notes(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-TEST-003',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Santunan Anak Yatim',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Paket Sembako',
            'jumlah_diminta' => 3000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);

        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->call('openAction', 'perbaikan')
            ->set('actionNotes', '')
            ->call('submitAction')
            ->assertHasErrors(['actionNotes']);

        $this->assertEquals('diajukan', $permohonan->fresh()->status);
    }

    public function test_final_approval_by_kadiv_keuangan_uses_item_table_without_scan_or_fr_prx(): void
    {
        $kadivKeuangan = User::where('username', 'direktur.keuangan')->firstOrFail();

        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-TEST-002',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Kemanusiaan',
            'nama_kegiatan' => 'Bantuan Bencana Alam',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Tenda dan Selimut',
            'jumlah_diminta' => 10000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        (new PermohonanWorkflow)->approve($permohonan, $this->kadivUser);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Staf keuangan non-Kadiv tidak berwenang melakukan persetujuan akhir
        Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertDontSee('Setujui Permohonan (Ikat Dana)');

        // Kadiv Keuangan menyetujui langsung dengan tabel nominal per item (tanpa FR-PRX dan tanpa upload bukti)
        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertSee('Persetujuan Akhir — Kepala Divisi Keuangan')
            ->assertDontSee('FR-PRX')
            ->assertDontSee('Pencatatan Fisik')
            ->assertDontSee('Unggah Scan Dokumen')
            ->set("approvedItemAmounts.{$item->id}", 9500000)
            ->set('proxyNotes', 'Disetujui sesuai revisi anggaran')
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $approved = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(9500000, $approved->total_disetujui);
        $this->assertEquals(9500000, $item->fresh()->jumlah_disetujui);
    }

    public function test_cetak_pdf_returns_application_pdf(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'SEK-PMH-2026-0099',
            'unit_id' => $this->unitSek->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Operasional',
            'nama_kegiatan' => 'Pengadaan Alat Tulis Kantor',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 3000000,
            'total_disetujui' => 3000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Kertas A4 dan Tinta Printer',
            'jumlah_diminta' => 3000000,
            'jumlah_disetujui' => 3000000,
        ]);

        $response = $this->actingAs($this->staffUser)->get("/permohonan/{$permohonan->id}/cetak-pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_can_submit_permohonan_with_dot_formatted_nominal(): void
    {
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('program', 'Sosial')
            ->set('nama_kegiatan', 'Bantuan Warga')
            ->set('user_id', $this->managerUser->id)
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Bantuan Tunai')
            ->set('items.0.jumlah_diminta', '1.500.000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permohonan', [
            'nama_kegiatan' => 'Bantuan Warga',
            'total_diminta' => 1500000,
            'status' => 'diajukan',
        ]);
    }

    public function test_detail_renders_successfully_when_status_is_dicairkan(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'SEK-PMH-2026-0888',
            'unit_id' => $this->unitSek->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Operasional',
            'nama_kegiatan' => 'Pengadaan Alat Kantor',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'dicairkan',
            'total_diminta' => 1500000,
            'total_disetujui' => 1500000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Dicairkan',
            'jumlah_diminta' => 1500000,
            'jumlah_disetujui' => 1500000,
        ]);
        $pencairan = Pencairan::create([
            'permohonan_id' => $permohonan->id,
            'no_ppd' => 'PPD/202610/0888',
            'tgl_cair' => now()->toDateString(),
            'jumlah' => 1500000,
            'status' => 'dicairkan',
        ]);

        Ump::create([
            'pencairan_id' => $pencairan->id,
            'tgl_terima' => now()->toDateString(),
            'tujuan' => $permohonan->nama_kegiatan,
            'pj_id' => $this->staffUser->id,
            'jumlah_total' => 1500000,
            'total_penggunaan' => 0,
            'saldo' => 1500000,
            'status' => 'draft',
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
        ]);

        $response = $this->actingAs($this->staffUser)->get("/permohonan/{$permohonan->id}");
        $response->assertStatus(200);
        $response->assertSee('Cetak Bukti PPD (PDF)');
        $response->assertSee('Lihat Laporan');
    }

    public function test_tu_must_select_pejabat_pemohon(): void
    {
        $tuUser = $this->staffUser;

        Livewire::actingAs($tuUser)
            ->test(PermohonanCreate::class)
            ->assertSee('Pengajuan oleh Petugas TU')
            ->set('program', 'Program Pelayanan TU')
            ->set('nama_kegiatan', 'Pengadaan Operasional TU Kosong')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Kertas dan ATK Kantor')
            ->set('items.0.jumlah_diminta', '1000000')
            ->call('submitNow')
            ->assertHasErrors(['user_id'])
            ->assertSee('Pejabat pemohon wajib dipilih.');

        $this->assertDatabaseMissing('permohonan', ['nama_kegiatan' => 'Pengadaan Operasional TU Kosong']);
    }

    public function test_non_tu_create_hides_tu_form_and_records_self(): void
    {
        Livewire::actingAs($this->managerUser)
            ->test(PermohonanCreate::class)
            ->assertSet('isTu', false)
            ->assertSet('user_id', $this->managerUser->id)
            ->assertDontSee('Pengajuan oleh Petugas TU')
            ->set('program', 'Program Humas')
            ->set('nama_kegiatan', 'Kegiatan Non TU')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Konsumsi Kegiatan')
            ->set('items.0.jumlah_diminta', '2000000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $pmh = Permohonan::where('nama_kegiatan', 'Kegiatan Non TU')->firstOrFail();
        $this->assertEquals($this->managerUser->id, $pmh->user_id);
        $this->assertEquals($this->managerUser->unit_id, $pmh->unit_id);
        $this->assertEquals('diajukan', $pmh->status);
    }

    public function test_tu_can_submit_and_approvals_proceed_without_wet_signature_or_scan(): void
    {
        // User staff TU Sekretariat memiliki is_petugas_input = true
        $tuUser = $this->staffUser;
        $kadivKeuangan = User::where('username', 'direktur.keuangan')->firstOrFail();
        $this->assertTrue($tuUser->is_petugas_input);

        // 1. TU membuat permohonan atas nama Pejabat Pemohon (Manager)
        Livewire::actingAs($tuUser)
            ->test(PermohonanCreate::class)
            ->assertSet('isTu', true)
            ->assertSee('Pengajuan oleh Petugas TU')
            ->set('program', 'Program Pelayanan TU')
            ->set('nama_kegiatan', 'Pengadaan Operasional TU')
            ->set('user_id', $this->managerUser->id)
            ->assertSet('unit_id', $this->managerUser->unit_id)
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Kertas dan ATK Kantor')
            ->set('items.0.jumlah_diminta', '3000000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $pmh = Permohonan::where('nama_kegiatan', 'Pengadaan Operasional TU')->firstOrFail();
        $this->assertEquals($this->managerUser->id, $pmh->user_id);
        $this->assertEquals($this->managerUser->unit_id, $pmh->unit_id);
        $this->assertEquals('diajukan', $pmh->status);

        // Pengusul TU tidak dapat menyetujui permohonan pada tahap Kadiv
        Livewire::actingAs($tuUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->set('actionType', 'setuju')
            ->call('submitAction')
            ->assertStatus(403);

        // 2. Kepala Divisi menyetujui Tahap 1 secara langsung (tanpa form tanda tangan fisik)
        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->call('openAction', 'setuju')
            ->set('actionNotes', 'Disetujui oleh Kepala Divisi')
            ->call('submitAction')
            ->assertHasNoErrors()
            ->assertSet('showActionModal', false);

        $pmh->refresh();
        $this->assertEquals('diketahui_kadiv', $pmh->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $pmh->id,
            'tahap' => 'kadiv',
            'keputusan' => 'setuju',
            'approver_id' => $this->kadivUser->id,
            'signer_name' => $this->kadivUser->name,
        ]);

        // 3. Kadiv Keuangan langsung menyetujui Tahap Akhir melalui tabel nominal per item (tanpa scan bukti)
        $item = $pmh->items->first();
        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->assertDontSee('FR-PRX')
            ->set("approvedItemAmounts.{$item->id}", 3000000)
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $pmh->refresh();
        $this->assertEquals('disetujui_keuangan', $pmh->status);
        $this->assertEquals(3000000, $pmh->total_disetujui);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Permohonan::class,
            'approvable_id' => $pmh->id,
            'tahap' => 'keuangan',
            'keputusan' => 'setuju',
            'approver_id' => $kadivKeuangan->id,
            'signer_name' => $kadivKeuangan->name,
        ]);
    }

    public function test_tu_automatically_sets_unit_when_pejabat_pemohon_is_selected(): void
    {
        $tuUser = $this->staffUser;
        $itUser = User::where('jabatan', 'like', '%IT%')->whereNotNull('unit_id')->first() ?? $this->managerUser;

        // 1. TU membuka form create: unit_id awal null, hasAutoUnit false
        $component = Livewire::actingAs($tuUser)
            ->test(PermohonanCreate::class)
            ->assertSet('isTu', true)
            ->assertSet('user_id', null)
            ->assertSet('unit_id', null)
            ->assertSet('hasAutoUnit', false)
            ->assertSee('Pilih Pejabat Pemohon di atas untuk menentukan unit/divisi secara otomatis.');

        // 2. TU memilih pemohon (misal Koordinator IT): unit_id otomatis terisi unit pemohon & hasAutoUnit true
        $component->set('user_id', $itUser->id)
            ->assertSet('unit_id', $itUser->unit_id)
            ->assertSet('hasAutoUnit', true)
            ->assertSee('Otomatis')
            ->assertSee($itUser->unit->nama);

        // 3. TU mengosongkan pilihan: unit_id kembali null
        $component->set('user_id', null)
            ->assertSet('unit_id', null)
            ->assertSet('hasAutoUnit', false);

        // 4. Non-TU user membuka form: unit_id otomatis terisi unit user
        Livewire::actingAs($this->managerUser)
            ->test(PermohonanCreate::class)
            ->assertSet('isTu', false)
            ->assertSet('unit_id', $this->managerUser->unit_id)
            ->assertSet('hasAutoUnit', true)
            ->assertSee('Otomatis');
    }

    public function test_submitnow_blocked_when_total_exceeds_pemohon_level_limit(): void
    {
        // supervisorUser (anas.priyogi, urutan=1, batas_atas=2000000)
        $supervisor = User::where('username', 'anas.priyogi')->firstOrFail();

        Livewire::actingAs($this->staffUser) // TU
            ->test(PermohonanCreate::class)
            ->set('user_id', $supervisor->id)      // sets unit_id automatically
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('nama_kegiatan', 'Kegiatan Over Limit')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('items.0.uraian', 'Item besar')
            ->set('items.0.jumlah_diminta', '3000000') // > 2000000 batas supervisor
            ->call('submitNow')
            ->assertHasErrors(['pemohon_limit'])
            ->assertSee('melebihi batas kewenangan');

        $this->assertDatabaseMissing('permohonan', ['nama_kegiatan' => 'Kegiatan Over Limit', 'user_id' => $supervisor->id, 'status' => 'diajukan']);

        // saveDraft succeeds for same amount
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('user_id', $supervisor->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('nama_kegiatan', 'Draft Over Limit OK')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('items.0.uraian', 'Item besar')
            ->set('items.0.jumlah_diminta', '3000000')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permohonan', ['nama_kegiatan' => 'Draft Over Limit OK', 'user_id' => $supervisor->id, 'status' => 'draft']);
    }

    public function test_final_approval_validates_item_amounts_without_requiring_scan_file(): void
    {
        $kadivKeuangan = User::where('username', 'direktur.keuangan')->firstOrFail();

        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-SCAN-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->managerUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Santunan Nominal Item Test',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'diketahui_kadiv',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Uji Nominal',
            'jumlah_diminta' => 5000000,
        ]);

        // Melebihi jumlah_diminta harus ditolak
        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->set("approvedItemAmounts.{$item->id}", 6000000)
            ->call('submitKeuanganApproval')
            ->assertHasErrors(["approvedItemAmounts.{$item->id}"]);

        // Total 0 harus ditolak
        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->set("approvedItemAmounts.{$item->id}", 0)
            ->call('submitKeuanganApproval')
            ->assertHasErrors(['approvedItemAmounts']);

        // Valid nominal tanpa scan file -> sukses
        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->set("approvedItemAmounts.{$item->id}", 4500000)
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $this->assertEquals('disetujui_keuangan', $permohonan->fresh()->status);
        $this->assertEquals(4500000, $permohonan->fresh()->total_disetujui);
    }

    public function test_supervisor_level_final_approval_by_kadiv_divisi(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-SPV-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Konsumsi Rapat Kecil',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Konsumsi Rapat',
            'jumlah_diminta' => 1500000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        (new PermohonanWorkflow)->approve($permohonan, $this->managerUser);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // Tahap akhir untuk supervisor level: Persetujuan Akhir — Kepala Divisi Sekretariat (tanpa bukti scan)
        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertSee('Persetujuan Akhir — Kepala Divisi Sekretariat')
            ->assertDontSee('FR-PRX')
            ->set("approvedItemAmounts.{$item->id}", 1400000)
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $this->assertEquals('disetujui_keuangan', $permohonan->fresh()->status);
        $this->assertEquals(1400000, $permohonan->fresh()->total_disetujui);
    }

    public function test_submit_permohonan_updates_ui_without_refresh(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-REACT-001',
            'unit_id' => $this->unitSek->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Reaktif',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaZakat->id,
            'status' => 'draft',
            'total_diminta' => 500000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Reaktif',
            'jumlah_diminta' => 500000,
        ]);

        $component = Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertSee('Draft');

        $component->call('submitPermohonan')
            ->assertSee('Menunggu Persetujuan')
            ->assertDontSee('Draft');
    }

    public function test_approve_action_updates_status_badge_without_refresh(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'REACT-APR-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Approve Reaktif',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Rapat',
            'jumlah_diminta' => 1000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        Livewire::actingAs($this->managerUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertSee('Menunggu Persetujuan')
            ->call('openAction', 'setuju')
            ->call('submitAction')
            ->assertSee('Menunggu Persetujuan Akhir');
    }

    public function test_keuangan_approval_updates_ui_cleanly(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'REACT-KEU-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Keuangan Reaktif',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Logistik',
            'jumlah_diminta' => 2000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        (new PermohonanWorkflow)->approve($permohonan, $this->managerUser);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->set("approvedItemAmounts.{$item->id}", 2000000)
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors()
            ->assertSee('Disetujui');
    }

    public function test_pj_ppd_ump_lists_managers_across_units_and_allows_any_manager_selection(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'PJ-UMP-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Operasional',
            'nama_kegiatan' => 'Pengadaan PJ UMP Lintas Unit',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 2000000,
            'total_disetujui' => 2000000,
        ]);

        $divisiId = $this->unitKlm->getDivisiId();
        $unitManager = $this->unitKlm->getManager();
        $this->assertNotNull($unitManager, 'Unit permohonan harus punya manager.');

        $pool = User::with(['unit', 'leveling'])
            ->where('is_active', true)
            ->whereHas('leveling', fn ($q) => $q->where('urutan', '>=', 2))
            ->orderBy('name')
            ->get();

        $sameDivisi = $pool->filter(fn (User $u) => $u->unit?->getDivisiId() === $divisiId);
        $outsideDivisi = $pool->filter(fn (User $u) => $u->unit?->getDivisiId() !== $divisiId);

        $this->assertGreaterThan(1, $pool->count(), 'Butuh beberapa manager lintas unit untuk menguji dropdown PJ.');
        $this->assertTrue($outsideDivisi->isNotEmpty(), 'Butuh manager dari divisi lain untuk menguji dropdown PJ.');

        $component = Livewire::actingAs($this->financeUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id]);

        /** @var array<int, array{id: int, name: string, sub: string}> $candidates */
        $candidates = $component->viewData('pjCandidates');
        $ids = array_column($candidates, 'id');

        // Semua manager/pimpinan (urutan >= 2) di seluruh unit & divisi tersedia di dropdown
        $this->assertSame(
            $pool->pluck('id')->sort()->values()->all(),
            collect($ids)->sort()->values()->all()
        );
        $this->assertGreaterThan(1, count($candidates), 'Dropdown PJ tidak boleh hanya berisi 1 manager.');

        // Manager unit permohonan selalu paling atas (default pilihan)
        $this->assertSame($unitManager->id, $candidates[0]['id']);

        // Manager satu divisi ditempatkan setelah manager unit, sebelum manager divisi lain
        $positions = array_flip($ids);
        $lastSameDivisi = max(array_map(fn (User $u) => $positions[$u->id], $sameDivisi->all()));
        $firstOutside = min(array_map(fn (User $u) => $positions[$u->id], $outsideDivisi->all()));
        $this->assertLessThan($firstOutside, $lastSameDivisi);

        // Setiap opsi menyertakan jabatan + unit pada label bantu
        foreach ($candidates as $candidate) {
            $this->assertNotSame('', $candidate['name']);
            $this->assertNotSame('', $candidate['sub']);
        }

        // Default pjId = manager unit, tanpa perlu memilih manual
        $component->call('openCairkanModal')->assertSet('pjId', $unitManager->id);

        // Manager dari divisi lain tetap dapat dipilih sebagai PJ PPD & UMP
        $otherPj = $outsideDivisi->first();
        $component->set('pjId', $otherPj->id)
            ->set('tglCair', now()->toDateString())
            ->call('submitCairkan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ump', [
            'pj_id' => $otherPj->id,
            'tujuan' => $permohonan->nama_kegiatan,
        ]);
        $this->assertEquals('dicairkan', $permohonan->fresh()->status);
    }

    public function test_permohonan_index_search_viewer_hiding_and_selesai_ui(): void
    {
        Permohonan::create([
            'no_pengajuan' => 'SEK-PMH-2026-7777',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Selesai',
            'nama_kegiatan' => 'Kegiatan Selesai Cari',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'selesai',
            'total_diminta' => 1000000,
            'total_disetujui' => 1000000,
        ]);

        // Pencarian di PermohonanIndex tidak crash ($term bug fixed)
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanIndex::class)
            ->set('search', 'Kegiatan Selesai Cari')
            ->assertSee('SEK-PMH-2026-7777')
            ->assertSee('Selesai');

        // Viewer tidak melihat tombol Buat Permohonan Baru dan mendapat 403 di /permohonan/buat
        $viewer = User::create([
            'name' => 'Viewer Tes',
            'username' => 'viewer.tes',
            'email' => 'viewer.tes@alazharpeduli.or.id',
            'password' => 'password',
            'role' => 'viewer',
            'unit_id' => $this->unitKlm->id,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)
            ->get('/permohonan')
            ->assertOk()
            ->assertDontSee('Buat Permohonan Baru');

        $this->actingAs($viewer)
            ->get('/permohonan/buat')
            ->assertStatus(403);
    }

    public function test_keuangan_role_submits_directly_without_tu_panel_and_kadiv_keuangan_auto_approves(): void
    {
        $kadivKeuangan = User::where('username', 'direktur.keuangan')->firstOrFail();

        Livewire::actingAs($this->financeUser)
            ->test(PermohonanCreate::class)
            ->assertSet('isTu', false)
            ->assertSet('user_id', $this->financeUser->id)
            ->assertDontSee('Pengajuan oleh Petugas TU');

        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanCreate::class)
            ->assertSet('isTu', false)
            ->assertSet('user_id', $kadivKeuangan->id)
            ->set('nama_kegiatan', 'Pengajuan Langsung Kadiv Keuangan')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Peralatan Audit')
            ->set('items.0.jumlah_diminta', '12000000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $pmh = Permohonan::where('nama_kegiatan', 'Pengajuan Langsung Kadiv Keuangan')->firstOrFail();
        $this->assertEquals('disetujui_keuangan', $pmh->status);
        $this->assertEquals(12000000, $pmh->total_disetujui);
        $this->assertEquals(12000000, $pmh->items()->first()->jumlah_disetujui);
    }

    public function test_security_boundaries_pdf_pengusul_direct_actions_and_dashboard_queue(): void
    {
        $supervisor = User::where('username', 'anas.priyogi')->firstOrFail();
        $siblingManager = User::where('username', 'sigit.nugroho')->firstOrFail();
        $otherDivUser = User::where('username', 'manager.program')->firstOrFail();
        $otherDivTu = User::create([
            'name' => 'TU Program',
            'username' => 'tu.program',
            'email' => 'tu.program@alazharpeduli.or.id',
            'password' => 'password',
            'role' => 'unit',
            'unit_id' => $otherDivUser->unit_id,
            'is_petugas_input' => true,
            'is_active' => true,
        ]);

        $pmh = Permohonan::create([
            'no_pengajuan' => 'SEC-BOUND-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $supervisor->id,
            'pengusul_id' => $otherDivTu->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Pengajuan Pengusul Lintas Divisi',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
        ]);

        PermohonanItem::create([
            'permohonan_id' => $pmh->id,
            'uraian' => 'Item Aman',
            'jumlah_diminta' => 1000000,
        ]);

        (new PermohonanWorkflow)->submit($pmh);

        // 1. pengusul_id dari divisi lain dapat mengakses detail & PDF, user divisi lain bukan pengusul ditolak 403
        $this->actingAs($otherDivTu)->get("/permohonan/{$pmh->id}")->assertOk();
        $this->actingAs($otherDivTu)->get("/permohonan/{$pmh->id}/cetak-pdf")->assertOk();
        $this->actingAs($otherDivUser)->get("/permohonan/{$pmh->id}/cetak-pdf")->assertStatus(403);

        // 2. Dashboard antrean hanya menampilkan ke Manajer Unit exact (deden.salim), bukan sibling (sigit) atau Kadiv
        Livewire::actingAs($this->managerUser)->test(Dashboard::class)->assertSee('SEC-BOUND-001');
        Livewire::actingAs($siblingManager)->test(Dashboard::class)->assertDontSee('SEC-BOUND-001');
        Livewire::actingAs($this->kadivUser)->test(Dashboard::class)->assertDontSee('SEC-BOUND-001');

        // 3. Direct Livewire submitAction / submitKeuanganApproval ditolak 403 bagi user tidak berwenang
        Livewire::actingAs($siblingManager)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->set('actionType', 'setuju')
            ->call('submitAction')
            ->assertStatus(403);

        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->call('submitKeuanganApproval')
            ->assertStatus(403);
    }

    public function test_non_tu_cannot_tamper_user_id_or_change_pemohon_and_staff_mounts_as_self(): void
    {
        $kadivKeuangan = User::where('username', 'direktur.keuangan')->firstOrFail();
        $supervisor = User::where('username', 'anas.priyogi')->firstOrFail();

        // 1. M2: Staff tanpa leveling di unit tanpa Supervisor tetap mount atas nama dirinya sendiri
        $divSek = Unit::where('kode', 'DIV-SEK')->firstOrFail();
        $unitNoSpv = Unit::create([
            'parent_id' => $divSek->id,
            'kode' => 'UNT-SEK-NOSPV',
            'nama' => 'Unit Tanpa Supervisor',
            'tipe' => 'unit',
        ]);
        $managerLevel = Leveling::where('urutan', 2)->firstOrFail();
        $managerUnit = User::create([
            'name' => 'Manajer Unit Tanpa SPV',
            'username' => 'manager.nospv',
            'email' => 'manager.nospv@alazharpeduli.or.id',
            'password' => 'password',
            'role' => 'unit',
            'leveling_id' => $managerLevel->id,
            'unit_id' => $unitNoSpv->id,
            'is_active' => true,
        ]);
        $plainStaff = User::create([
            'name' => 'Staf Biasa Tanpa Leveling',
            'username' => 'staff.biasa.nospv',
            'email' => 'staff.biasa.nospv@alazharpeduli.or.id',
            'password' => 'password',
            'role' => 'unit',
            'leveling_id' => null,
            'unit_id' => $unitNoSpv->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($plainStaff)
            ->test(PermohonanCreate::class)
            ->assertSet('user_id', $plainStaff->id)
            ->assertSet('unit_id', $unitNoSpv->id)
            ->assertSet('pemohonLimit', 2000000)
            ->set('items.0.jumlah_diminta', '2500000')
            ->assertSet('isOverPemohonLimit', true)
            ->set('nama_kegiatan', 'Pengajuan Staf Biasa')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'ATK Unit')
            ->set('items.0.jumlah_diminta', '1500000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $staffPmh = Permohonan::where('nama_kegiatan', 'Pengajuan Staf Biasa')->firstOrFail();
        $this->assertEquals($plainStaff->id, $staffPmh->user_id);
        $this->assertEquals('diajukan', $staffPmh->status);
        $this->assertEquals($managerUnit->id, $staffPmh->atasan_id);

        // 2. C1: Non-TU mencoba memanipulasi user_id -> direktur.keuangan di PermohonanCreate diblokir / dikembalikan ke dirinya
        Livewire::actingAs($supervisor)
            ->test(PermohonanCreate::class)
            ->set('user_id', $kadivKeuangan->id)
            ->set('unit_id', $kadivKeuangan->unit_id)
            ->assertSet('user_id', $supervisor->id)
            ->set('nama_kegiatan', 'Tamper Create Non TU')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Item')
            ->set('items.0.jumlah_diminta', '1000000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $tamperCreate = Permohonan::where('nama_kegiatan', 'Tamper Create Non TU')->firstOrFail();
        $this->assertEquals($supervisor->id, $tamperCreate->user_id);
        $this->assertEquals('diajukan', $tamperCreate->status);

        // 3. C1: Non-TU mencoba memanipulasi user_id di PermohonanEdit & membuka modal Alih Pejabat di PermohonanDetail
        $draft = Permohonan::create([
            'no_pengajuan' => 'DRAFT-C1-001',
            'unit_id' => $supervisor->unit_id,
            'user_id' => $supervisor->id,
            'pengusul_id' => $supervisor->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program',
            'nama_kegiatan' => 'Draft Tamper Edit',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 1000000,
        ]);
        PermohonanItem::create([
            'permohonan_id' => $draft->id,
            'uraian' => 'Item Draft',
            'jumlah_diminta' => 1000000,
        ]);

        Livewire::actingAs($supervisor)
            ->test(PermohonanDetail::class, ['id' => $draft->id])
            ->assertDontSee('Alih Pejabat Pemohon')
            ->call('openChangePemohonModal')
            ->assertStatus(403);

        Livewire::actingAs($supervisor)
            ->test(PermohonanDetail::class, ['id' => $draft->id])
            ->set('newPemohonId', $kadivKeuangan->id)
            ->call('updatePejabatPemohon')
            ->assertStatus(403);

        Livewire::actingAs($supervisor)
            ->test(PermohonanEdit::class, ['id' => $draft->id])
            ->set('user_id', $kadivKeuangan->id)
            ->set('unit_id', $kadivKeuangan->unit_id)
            ->assertSet('user_id', $supervisor->id)
            ->call('submitNow')
            ->assertHasNoErrors();

        $draft->refresh();
        $this->assertEquals($supervisor->id, $draft->user_id);
        $this->assertEquals('diajukan', $draft->status);
    }

    public function test_tu_submitting_for_kadiv_keuangan_via_ui_requires_kadiv_keuangan_confirmation(): void
    {
        $kadivKeuangan = User::where('username', 'direktur.keuangan')->firstOrFail();

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('user_id', $kadivKeuangan->id)
            ->assertDontSee('Disetujui Otomatis')
            ->set('nama_kegiatan', 'TU Untuk Kadiv Keuangan UI')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $this->sumberDanaInfak->id)
            ->set('kode_anggaran_id', $this->leafNode->id)
            ->set('items.0.uraian', 'Perangkat Keuangan')
            ->set('items.0.jumlah_diminta', '15000000')
            ->call('submitNow')
            ->assertHasNoErrors();

        $pmh = Permohonan::where('nama_kegiatan', 'TU Untuk Kadiv Keuangan UI')->firstOrFail();
        $this->assertEquals('diketahui_kadiv', $pmh->status);
        $this->assertNull($pmh->total_disetujui);

        // TU pengusul tidak boleh mengonfirmasi persetujuan
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->assertSet('canApprove', false)
            ->call('submitKeuanganApproval')
            ->assertStatus(403);

        // Kadiv Keuangan masuk untuk mengonfirmasi persetujuan akhir
        Livewire::actingAs($kadivKeuangan)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->assertSet('canApprove', true)
            ->set('proxyNotes', 'Disetujui oleh Kadiv Keuangan')
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $this->assertEquals('disetujui_keuangan', $pmh->fresh()->status);
        $this->assertEquals(15000000, $pmh->fresh()->total_disetujui);
    }

    public function test_permohonan_detail_and_edit_lock_permohonan_id_against_idor_tampering(): void
    {
        $managerProgram = User::where('username', 'manager.program')->firstOrFail();

        $divSekPmh = Permohonan::create([
            'no_pengajuan' => 'IDOR-SEK-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'pengusul_id' => $this->kadivUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sekretariat',
            'nama_kegiatan' => 'Rahasia Divisi Sekretariat',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'diajukan',
            'total_diminta' => 5000000,
        ]);

        $divSekDraft = Permohonan::create([
            'no_pengajuan' => 'IDOR-SEK-DRAFT',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->kadivUser->id,
            'pengusul_id' => $this->kadivUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sekretariat',
            'nama_kegiatan' => 'Draft Rahasia Divisi Sekretariat',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 5000000,
        ]);

        $ownPmh = Permohonan::create([
            'no_pengajuan' => 'OWN-PRG-001',
            'unit_id' => $managerProgram->unit_id,
            'user_id' => $managerProgram->id,
            'pengusul_id' => $managerProgram->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sendiri',
            'nama_kegiatan' => 'Permohonan Milik Program',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'draft',
            'total_diminta' => 3000000,
        ]);

        try {
            Livewire::actingAs($managerProgram)
                ->test(PermohonanDetail::class, ['id' => $ownPmh->id])
                ->set('permohonanId', $divSekPmh->id);
            $this->fail('Expected CannotUpdateLockedPropertyException for PermohonanDetail::$permohonanId');
        } catch (CannotUpdateLockedPropertyException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        try {
            Livewire::actingAs($managerProgram)
                ->test(PermohonanEdit::class, ['id' => $ownPmh->id])
                ->set('permohonanId', $divSekDraft->id);
            $this->fail('Expected CannotUpdateLockedPropertyException for PermohonanEdit::$permohonanId');
        } catch (CannotUpdateLockedPropertyException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
}
