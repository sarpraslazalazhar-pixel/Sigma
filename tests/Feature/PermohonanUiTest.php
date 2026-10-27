<?php

namespace Tests\Feature;

use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Models\Asnaf;
use App\Models\KodeAnggaran;
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
use Illuminate\Http\UploadedFile;
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
            'user_id' => $this->staffUser->id,
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
            'jumlah_diminta' => 15000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Akses halaman detail oleh Kepala Divisi Sekretariat
        $this->actingAs($this->kadivUser)
            ->get("/permohonan/{$permohonan->id}")
            ->assertStatus(200)
            ->assertSee('Santunan Rutin Yatim')
            ->assertSee('Setujui Dokumen');

        // Tahap 2: Kepala Divisi Sekretariat memeriksa (default penanda tangan sudah terisi)
        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->call('openAction', 'setuju')
            ->assertSet('pemeriksaName', $this->kadivUser->name)
            ->assertSet('pemeriksaJabatan', $this->kadivUser->jabatan)
            ->set('actionNotes', 'Diperiksa Kepala Divisi Sekretariat')
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

    public function test_stage_two_requires_wet_signature_identity(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-TEST-003',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
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
            ->call('openAction', 'setuju')
            ->set('pemeriksaName', '')
            ->set('pemeriksaJabatan', '')
            ->set('pemeriksaSignedAt', '')
            ->call('submitAction')
            ->assertHasErrors(['pemeriksaName', 'pemeriksaJabatan', 'pemeriksaSignedAt']);

        $this->assertEquals('diajukan', $permohonan->fresh()->status);
    }

    public function test_proxy_keuangan_approval_mode(): void
    {
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

        // Disetujui kadiv langsung
        (new PermohonanWorkflow)->approve($permohonan, $this->kadivUser);
        $this->assertEquals('diketahui_kadiv', $permohonan->fresh()->status);

        // User staff TU Sekretariat memiliki is_petugas_input = true
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertSee('Persetujuan Akhir Kadiv Keuangan (Pencatatan Fisik / Input Terpusat)')
            ->set('proxySignerName', 'Direktur Keuangan')
            ->set('proxySignerJabatan', 'Kepala Divisi Keuangan / Direktur Keuangan')
            ->set('proxySignedAt', now()->toDateString())
            ->set('proxyScanFile', UploadedFile::fake()->create('bukti_kadiv_keu.pdf', 100, 'application/pdf'))
            ->set("approvedItemAmounts.{$item->id}", 9500000)
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $approved = $permohonan->fresh();
        $this->assertEquals('disetujui_keuangan', $approved->status);
        $this->assertEquals(9500000, $approved->total_disetujui);
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
        $response->assertSee('Lihat UMP');
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

    public function test_tu_can_submit_and_record_approvals_in_detail(): void
    {
        // User staff TU Sekretariat memiliki is_petugas_input = true
        $tuUser = $this->staffUser;
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

        // 2. TU mencatat pemeriksaan Tahap 2 atas nama Kepala Divisi Sekretariat
        Livewire::actingAs($tuUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->call('openAction', 'setuju')
            ->assertSee('Pilih Pejabat Pemeriksa')
            ->set('pemeriksaId', $this->kadivUser->id)
            ->assertSet('pemeriksaName', $this->kadivUser->name)
            ->assertSet('pemeriksaJabatan', $this->kadivUser->jabatan)
            ->set('pemeriksaSignedAt', now()->toDateString())
            ->set('actionNotes', 'Tanda tangan fisik Kadiv Sekretariat dicatat')
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
            'recorded_by' => $tuUser->id,
        ]);

        // 3. TU di tahap Keuangan memilih preset penanda tangan keuangan dan menyetujui
        $item = $pmh->items->first();
        Livewire::actingAs($tuUser)
            ->test(PermohonanDetail::class, ['id' => $pmh->id])
            ->assertSee('Pilihan Cepat Penanda Tangan')
            ->set('financeSignerPreset', 'Direktur Keuangan|Direktur Keuangan')
            ->assertSet('proxySignerName', 'Direktur Keuangan')
            ->assertSet('proxySignerJabatan', 'Direktur Keuangan')
            ->set("approvedItemAmounts.{$item->id}", 3000000)
            ->set('proxySignedAt', now()->toDateString())
            ->set('proxyScanFile', UploadedFile::fake()->create('bukti_persetujuan.pdf', 100, 'application/pdf'))
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
            'signer_name' => 'Direktur Keuangan',
            'signer_jabatan' => 'Direktur Keuangan',
            'recorded_by' => $tuUser->id,
        ]);
    }

    public function test_final_approval_requires_uploaded_scan_file(): void
    {
        $permohonan = Permohonan::create([
            'no_pengajuan' => 'DRAFT-SCAN-001',
            'unit_id' => $this->unitKlm->id,
            'user_id' => $this->staffUser->id,
            'kode_anggaran_id' => $this->leafNode->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Santunan Scan Test',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->sumberDanaInfak->id,
            'status' => 'diketahui_kadiv',
        ]);

        $item = PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Item Scan Test',
            'jumlah_diminta' => 5000000,
        ]);

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->set('proxySignerName', 'Direktur Keuangan')
            ->set('proxySignerJabatan', 'Direktur Keuangan')
            ->set('proxySignedAt', now()->toDateString())
            ->set("approvedItemAmounts.{$item->id}", 5000000)
            ->set('proxyScanFile', null)
            ->call('submitKeuanganApproval')
            ->assertHasErrors(['proxyScanFile']);
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

        // Tahap akhir untuk supervisor level: Persetujuan Akhir Kepala Divisi
        Livewire::actingAs($this->kadivUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->assertSee('Persetujuan Akhir Kepala Divisi (Pencatatan Fisik / Input Terpusat)')
            ->assertSet('proxySignerName', $this->kadivUser->name)
            ->set('proxySignedAt', now()->toDateString())
            ->set('proxyScanFile', UploadedFile::fake()->create('scan_kadiv_divisi.pdf', 100, 'application/pdf'))
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors();

        $this->assertEquals('disetujui_keuangan', $permohonan->fresh()->status);
        $this->assertEquals(1500000, $permohonan->fresh()->total_disetujui);
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
            ->set('pemeriksaSignedAt', now()->toDateString())
            ->call('submitAction')
            ->assertSee('Menunggu Persetujuan Akhir');
    }

    public function test_keuangan_approval_updates_ui_and_clears_file_input(): void
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
            ->set('proxySignedAt', now()->toDateString())
            ->set('proxyScanFile', UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'))
            ->call('submitKeuanganApproval')
            ->assertHasNoErrors()
            ->assertSet('proxyScanFile', null)
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
}
