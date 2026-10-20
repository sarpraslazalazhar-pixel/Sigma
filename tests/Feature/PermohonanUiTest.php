<?php

namespace Tests\Feature;

use App\Livewire\Permohonan\PermohonanCreate;
use App\Livewire\Permohonan\PermohonanDetail;
use App\Models\Asnaf;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\ProgramUtama;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->staffUser = User::where('username', 'tu.sekretariat')->firstOrFail();
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
            ->assertSee('+ Buat Permohonan Baru');

        $this->actingAs($this->staffUser)
            ->get('/permohonan/buat')
            ->assertStatus(200)
            ->assertSee('Buat Permohonan Dana')
            ->assertSee('Informasi Pengajuan');
    }

    public function test_validation_submit_multi_row_items(): void
    {
        // Gagal karena uraian dan nominal kosong
        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('program', 'Program Sosial')
            ->set('nama_kegiatan', 'Kegiatan Tanpa Rincian')
            ->set('unit_id', $this->unitSek->id)
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
            ->set('unit_id', $this->unitSek->id)
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
            ->set('unit_id', $this->unitSek->id)
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
            ->set('unit_id', $this->unitSek->id)
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
            'jumlah_diminta' => 5000000,
        ]);

        (new PermohonanWorkflow)->submit($permohonan);
        $this->assertEquals('diajukan', $permohonan->fresh()->status);

        // Akses halaman detail
        $this->actingAs($this->managerUser)
            ->get("/permohonan/{$permohonan->id}")
            ->assertStatus(200)
            ->assertSee('Santunan Rutin Yatim')
            ->assertSee('Setujui Dokumen');

        // Atasan manager menyetujui
        Livewire::actingAs($this->managerUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->call('openAction', 'setuju')
            ->set('actionNotes', 'Disetujui oleh manager')
            ->call('submitAction')
            ->assertHasNoErrors();

        $this->assertEquals('disetujui_atasan', $permohonan->fresh()->status);
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
            ->assertSee('Persetujuan Bagian Keuangan (Mode Atas Nama / Input Terpusat)')
            ->set('proxySignerName', 'Rochadi Kohar')
            ->set('proxySignerJabatan', 'Manajer Anggaran')
            ->set('proxySignedAt', now()->toDateString())
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
            ->set('unit_id', $this->unitSek->id)
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
}
