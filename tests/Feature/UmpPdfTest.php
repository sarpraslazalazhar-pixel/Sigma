<?php

namespace Tests\Feature;

use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UmpPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;

    protected Ump $ump;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $pjUser = User::where('username', 'subakti')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '95.1',
            'nama' => 'Leaf Tes PDF UMP',
            'depth' => 1,
            'plafon' => 25000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9401',
            'unit_id' => $unit->id,
            'user_id' => $pjUser->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan Cetak PDF UMP',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Rincian kegiatan PDF UMP',
            'jumlah_diminta' => 5000000,
            'jumlah_disetujui' => 5000000,
        ]);

        $pencairan = (new PermohonanWorkflow)->cairkan($permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0300',
            'tgl_cair' => now()->toDateString(),
        ]);

        $this->ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
    }

    public function test_print_ump_pdf_returns_pdf(): void
    {
        $response = $this->actingAs($this->financeUser)->get("/ump/{$this->ump->id}/cetak-pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get("/ump/{$this->ump->id}/cetak-pdf")->assertRedirect(route('login'));
    }
}
