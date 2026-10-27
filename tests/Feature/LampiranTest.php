<?php

namespace Tests\Feature;

use App\Models\KodeAnggaran;
use App\Models\Lampiran;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LampiranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);
    }

    private function createPermohonan(User $user): Permohonan
    {
        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();
        $kode = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '99.1',
            'nama' => 'Node Lampiran Test',
            'depth' => 1,
            'plafon' => 5000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        return Permohonan::create([
            'no_pengajuan' => 'TEST-LAMP-'.uniqid(),
            'unit_id' => $user->unit_id,
            'user_id' => $user->id,
            'kode_anggaran_id' => $kode->id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Tes',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'diajukan',
            'total_diminta' => 1000000,
            'total_disetujui' => 1000000,
        ]);
    }

    public function test_lampiran_served_from_local_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $filePath = 'lampiran/test-local.pdf';
        Storage::disk('local')->put($filePath, 'local content');

        $user = User::where('username', 'deden.salim')->firstOrFail();
        $permohonan = $this->createPermohonan($user);

        $lampiran = Lampiran::create([
            'attachable_type' => Permohonan::class,
            'attachable_id' => $permohonan->id,
            'original_name' => 'dokumen-lokal.pdf',
            'path' => $filePath,
            'mime' => 'application/pdf',
            'size' => 13,
            'jenis' => 'bukti',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lampiran.show', $lampiran->id));
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('dokumen-lokal.pdf', $response->headers->get('content-disposition'));
    }

    public function test_lampiran_served_from_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $filePath = 'lampiran/scan_final/test-public.pdf';
        Storage::disk('public')->put($filePath, 'public content');

        $user = User::where('username', 'deden.salim')->firstOrFail();
        $permohonan = $this->createPermohonan($user);

        $lampiran = Lampiran::create([
            'attachable_type' => Permohonan::class,
            'attachable_id' => $permohonan->id,
            'original_name' => 'scan-final.pdf',
            'path' => $filePath,
            'mime' => 'application/pdf',
            'size' => 14,
            'jenis' => 'scan_final',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lampiran.show', $lampiran->id));
        $response->assertStatus(200);
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('scan-final.pdf', $response->headers->get('content-disposition'));
    }

    public function test_lampiran_returns_404_when_file_missing_on_both_disks(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::where('username', 'deden.salim')->firstOrFail();
        $permohonan = $this->createPermohonan($user);

        $lampiran = Lampiran::create([
            'attachable_type' => Permohonan::class,
            'attachable_id' => $permohonan->id,
            'original_name' => 'ghost.pdf',
            'path' => 'lampiran/nonexistent.pdf',
            'mime' => 'application/pdf',
            'size' => 0,
            'jenis' => 'bukti',
            'uploaded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lampiran.show', $lampiran->id));
        $response->assertStatus(404);
    }
}
