<?php

namespace Tests\Feature;

use App\Livewire\Permohonan\PermohonanDetail;
use App\Livewire\Ump\UmpDetail;
use App\Models\KodeAnggaran;
use App\Models\Lampiran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpItem;
use App\Models\UmpPenerima;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use App\Services\UmpWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UmpPenerimaTest extends TestCase
{
    use RefreshDatabase;

    protected User $tuUser;

    protected User $pjUser;

    protected User $penerimaA;

    protected User $penerimaB;

    protected Permohonan $permohonan;

    protected Ump $ump;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->tuUser = User::where('username', 'subakti')->firstOrFail();
        $this->pjUser = User::where('username', 'deden.salim')->firstOrFail();
        $this->penerimaA = User::where('username', 'lias')->firstOrFail();
        $this->penerimaB = User::where('username', 'suryamin')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '98',
            'nama' => 'Tes UMP Penerima',
            'depth' => 1,
            'plafon' => 30000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9301',
            'unit_id' => $unit->id,
            'user_id' => $this->pjUser->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan UMP Penerima',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $this->permohonan->id,
            'uraian' => 'Rincian kegiatan UMP penerima',
            'jumlah_diminta' => 5000000,
            'jumlah_disetujui' => 5000000,
        ]);

        $pencairan = (new PermohonanWorkflow)->cairkan($this->permohonan, $this->tuUser, [
            'no_ppd' => 'PPD/KEU/2026/0200',
            'tgl_cair' => now()->toDateString(),
            'pj_id' => $this->pjUser->id,
            'penerima_ids' => [$this->penerimaA->id, $this->penerimaB->id],
        ]);

        $this->ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
    }

    public function test_cairkan_creates_penerima_records_and_notifies(): void
    {
        $this->assertSame(2, UmpPenerima::where('ump_id', $this->ump->id)->count());

        $this->assertDatabaseHas('ump_penerima', [
            'ump_id' => $this->ump->id,
            'user_id' => $this->penerimaA->id,
        ]);

        $this->assertSame(
            1,
            DB::table('notifications')
                ->where('dedup_key', "ump_penerima_{$this->ump->id}_{$this->penerimaA->id}")
                ->count()
        );
    }

    public function test_save_items_links_penerima_and_aggregates_totals(): void
    {
        $pivot = UmpPenerima::where('ump_id', $this->ump->id)
            ->where('user_id', $this->penerimaA->id)
            ->firstOrFail();

        (new UmpWorkflow)->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Baris PJ', 'no_bukti' => 'A-1', 'jumlah' => 1000000],
            ['tgl' => now()->toDateString(), 'rincian' => 'Baris Penerima A', 'no_bukti' => 'B-1', 'jumlah' => 1500000, 'penerima_id' => $pivot->id],
        ]);

        $ump = $this->ump->fresh();

        $this->assertSame(2500000, (int) $ump->total_penggunaan);

        $pdfItem = UmpItem::where('ump_id', $ump->id)->where('rincian', 'Baris PJ')->firstOrFail();
        $this->assertNull($pdfItem->penerima_id);

        $penerimaItem = UmpItem::where('ump_id', $ump->id)->where('rincian', 'Baris Penerima A')->firstOrFail();
        $this->assertSame($pivot->id, $penerimaItem->penerima_id);
    }

    public function test_penerima_can_only_edit_own_section_via_ui(): void
    {
        $pivot = UmpPenerima::where('ump_id', $this->ump->id)
            ->where('user_id', $this->penerimaA->id)
            ->firstOrFail();

        Livewire::actingAs($this->penerimaA)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('tambahBaris', 'pj')
            ->assertCount('itemsByPenerima.pj', 0)
            ->call('tambahBaris', (string) $pivot->id)
            ->assertCount('itemsByPenerima.'.$pivot->id, 1);
    }

    public function test_tu_can_edit_all_sections_via_ui(): void
    {
        $pivot = UmpPenerima::where('ump_id', $this->ump->id)
            ->where('user_id', $this->penerimaA->id)
            ->firstOrFail();

        Livewire::actingAs($this->tuUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('tambahBaris', 'pj')
            ->assertCount('itemsByPenerima.pj', 1)
            ->call('tambahBaris', (string) $pivot->id)
            ->assertCount('itemsByPenerima.'.$pivot->id, 1);
    }

    public function test_sync_penerima_updates_assignments(): void
    {
        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->set('selectedPenerimaIds', [$this->penerimaB->id])
            ->call('syncPenerima')
            ->assertHasNoErrors();

        $this->assertSame(1, UmpPenerima::where('ump_id', $this->ump->id)->count());
        $this->assertDatabaseHas('ump_penerima', [
            'ump_id' => $this->ump->id,
            'user_id' => $this->penerimaB->id,
        ]);
        $this->assertDatabaseMissing('ump_penerima', [
            'ump_id' => $this->ump->id,
            'user_id' => $this->penerimaA->id,
        ]);
    }

    public function test_per_item_bukti_upload_creates_lampiran(): void
    {
        Storage::fake('local');

        (new UmpWorkflow)->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Belanja bahan', 'no_bukti' => 'X-1', 'jumlah' => 500000],
        ]);

        $item = UmpItem::where('ump_id', $this->ump->id)->firstOrFail();

        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('setBuktiItemTarget', $item->id)
            ->set('buktiItem', UploadedFile::fake()->image('bukti-tf.jpg', 200, 200))
            ->call('simpanBuktiItem', $item->id)
            ->assertHasNoErrors()
            ->assertSee('bukti-tf.jpg');

        $lampiran = Lampiran::where('attachable_type', UmpItem::class)
            ->where('attachable_id', $item->id)
            ->first();

        $this->assertNotNull($lampiran);
        $this->assertSame('bukti_tf', $lampiran->jenis);
        Storage::disk('local')->assertExists($lampiran->path);
    }

    public function test_policy_allows_assigned_penerima_view_and_update(): void
    {
        $this->assertTrue(Gate::forUser($this->penerimaA)->allows('view', $this->ump));
        $this->assertTrue(Gate::forUser($this->penerimaA)->allows('update', $this->ump));

        $outsider = User::where('username', 'manager.program')->firstOrFail();
        $this->assertFalse(Gate::forUser($outsider)->allows('update', $this->ump));
    }

    public function test_cairkan_modal_exposes_penerima_picker_and_creates_assignments(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '99',
            'nama' => 'Tes Modal Cairkan',
            'depth' => 1,
            'plafon' => 10000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9302',
            'unit_id' => $unit->id,
            'user_id' => $this->pjUser->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan Modal Cairkan',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 2000000,
            'total_disetujui' => 2000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $permohonan->id,
            'uraian' => 'Rincian modal cairkan',
            'jumlah_diminta' => 2000000,
            'jumlah_disetujui' => 2000000,
        ]);

        Livewire::actingAs($this->tuUser)
            ->test(PermohonanDetail::class, ['id' => $permohonan->id])
            ->call('openCairkanModal')
            ->assertSee('Penerima Dana')
            ->set('penerimaIds', [$this->penerimaA->id, $this->penerimaB->id])
            ->call('submitCairkan')
            ->assertHasNoErrors();

        $ump = Ump::whereHas('pencairan', fn ($q) => $q->where('permohonan_id', $permohonan->id))->firstOrFail();
        $this->assertSame(2, UmpPenerima::where('ump_id', $ump->id)->count());
    }
}
