<?php

namespace Tests\Feature;

use App\Livewire\Ump\UmpDetail;
use App\Livewire\Ump\UmpIndex;
use App\Models\KodeAnggaran;
use App\Models\Permohonan;
use App\Models\PermohonanItem;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\Unit;
use App\Models\User;
use App\Services\PermohonanWorkflow;
use App\Services\UmpWorkflow;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class UmpWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $financeUser;

    protected User $pjUser;

    protected Permohonan $permohonan;

    protected Ump $ump;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->financeUser = User::where('username', 'rochadi.kohar')->firstOrFail();
        $this->pjUser = User::where('username', 'subakti')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '97',
            'nama' => 'Tes UMP',
            'depth' => 1,
            'plafon' => 30000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9201',
            'unit_id' => $unit->id,
            'user_id' => $this->pjUser->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Sosial',
            'nama_kegiatan' => 'Kegiatan UMP Tes',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'disetujui_keuangan',
            'total_diminta' => 5000000,
            'total_disetujui' => 5000000,
        ]);

        PermohonanItem::create([
            'permohonan_id' => $this->permohonan->id,
            'uraian' => 'Rincian kegiatan UMP',
            'jumlah_diminta' => 5000000,
            'jumlah_disetujui' => 5000000,
        ]);

        $pencairan = (new PermohonanWorkflow)->cairkan($this->permohonan, $this->financeUser, [
            'no_ppd' => 'PPD/KEU/2026/0100',
            'tgl_cair' => now()->toDateString(),
        ]);

        $this->ump = Ump::where('pencairan_id', $pencairan->id)->firstOrFail();
    }

    public function test_save_items_calculates_totals_and_pembulatan(): void
    {
        (new UmpWorkflow)->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Transport tim', 'no_bukti' => 'TRF-001', 'jumlah' => 2000400],
            ['tgl' => now()->toDateString(), 'rincian' => 'Konsumsi kegiatan', 'no_bukti' => 'NOTA-002', 'jumlah' => 1500000],
        ]);

        $ump = $this->ump->fresh();

        $this->assertSame(3500400, (int) $ump->total_penggunaan);
        $this->assertSame(1499600, (int) $ump->saldo);
        $this->assertSame(0, (int) $ump->kekurangan);
        $this->assertSame(1500000, (int) $ump->pembulatan); // 1.499.600 dibulatkan ke kelipatan 1.000 terdekat
        $this->assertSame(2, $ump->items()->count());

        // Sinkronisasi: baris hilang dari input dihapus, baris baru ditambahkan
        (new UmpWorkflow)->saveItems($this->ump, [
            ['id' => $ump->items()->first()->id, 'tgl' => now()->toDateString(), 'rincian' => 'Transport tim (diperbarui)', 'no_bukti' => 'TRF-001', 'jumlah' => 1000000],
        ]);

        $ump->refresh();
        $this->assertSame(1, $ump->items()->count());
        $this->assertSame(1000000, (int) $ump->total_penggunaan);
        $this->assertSame('Transport tim (diperbarui)', $ump->items()->first()->rincian);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ump.items_saved',
            'entity_id' => $this->ump->id,
        ]);
    }

    public function test_deficit_records_kekurangan_and_negative_saldo(): void
    {
        (new UmpWorkflow)->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Realisasi melebihi dana', 'jumlah' => 6000000],
        ]);

        $ump = $this->ump->fresh();

        $this->assertSame(6000000, (int) $ump->total_penggunaan);
        $this->assertSame(-1000000, (int) $ump->saldo);
        $this->assertSame(1000000, (int) $ump->kekurangan);
        $this->assertSame(-1000000, (int) $ump->pembulatan);

        // Defisit tanpa catatan alasan ditolak (FR-UMP-6)
        try {
            (new UmpWorkflow)->submitByPj($ump, $this->pjUser, '');
            $this->fail('Submit defisit tanpa catatan seharusnya gagal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('melebihi dana', $e->getMessage());
        }

        $this->assertSame('draft', $ump->fresh()->status);
    }

    public function test_submit_by_pj_transitions_status_and_records_approval(): void
    {
        $workflow = new UmpWorkflow;
        $workflow->saveItems($this->ump, [
            ['tgl' => now()->toDateString(), 'rincian' => 'Pengeluaran kegiatan', 'jumlah' => 4000000],
        ]);

        $workflow->submitByPj($this->ump, $this->pjUser, '');

        $ump = $this->ump->fresh();
        $this->assertSame('ttd_pj', $ump->status);

        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Ump::class,
            'approvable_id' => $ump->id,
            'siklus' => 1,
            'tahap' => 'ttd_pj',
            'keputusan' => 'setuju',
            'approver_id' => $this->pjUser->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ump.submitted_pj',
            'entity_id' => $ump->id,
        ]);

        // Status non-editable ditolak untuk submit ulang
        $this->expectException(RuntimeException::class);
        $workflow->submitByPj($ump, $this->pjUser, '');
    }

    public function test_submit_without_items_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('minimal satu baris');

        (new UmpWorkflow)->submitByPj($this->ump, $this->pjUser, '');
    }

    public function test_ui_index_renders_with_metrics(): void
    {
        $this->actingAs($this->financeUser)
            ->get(route('ump.index'))
            ->assertStatus(200)
            ->assertSee('Laporan UMP')
            ->assertSee('Total UMP Berjalan')
            ->assertSee('Total Dana Diterima')
            ->assertSee('Total Realisasi Penggunaan')
            ->assertSee('Saldo Tersisa')
            ->assertSee('PPD/KEU/2026/0100');

        Livewire::actingAs($this->financeUser)
            ->test(UmpIndex::class)
            ->set('search', 'PPD/KEU/2026/0100')
            ->assertSee('Kegiatan UMP Tes')
            ->set('statusFilter', 'draft')
            ->assertSee('Buka Form UMP');
    }

    public function test_ui_detail_add_row_save_and_submit(): void
    {
        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->assertSee('Bagian A: Jumlah Diterima')
            ->assertSee('Bagian B: Penggunaan Dana')
            ->assertSee('Bagian C: Saldo / Defisit')
            ->assertSee('Bagian D: Pembulatan Nominal')
            ->assertSee('Rp 5.000.000')
            ->call('tambahBaris')
            ->assertCount('items', 1)
            ->set('items.0.tgl', now()->toDateString())
            ->set('items.0.rincian', 'Sewa kendaraan operasional')
            ->set('items.0.no_bukti', 'KUIT-001')
            ->set('items.0.jumlah', 1200000)
            ->call('simpanDraft')
            ->assertHasNoErrors()
            ->assertSee('Draft UMP tersimpan');

        $this->ump->refresh();
        $this->assertSame(1200000, (int) $this->ump->total_penggunaan);
        $this->assertSame(3800000, (int) $this->ump->saldo);
        $this->assertSame(3800000, (int) $this->ump->pembulatan);

        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('ajukanUMP')
            ->assertHasNoErrors()
            ->assertSee('UMP berhasil diajukan');

        $this->assertSame('ttd_pj', $this->ump->fresh()->status);

        // Form UMP tidak dapat diedit lagi setelah diajukan
        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->assertDontSee('Ajukan / Tanda Tangan PJ')
            ->assertSee('tidak dapat diubah');
    }

    public function test_ui_detail_deficit_requires_catatan(): void
    {
        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('tambahBaris')
            ->set('items.0.tgl', now()->toDateString())
            ->set('items.0.rincian', 'Pengeluaran melebihi dana')
            ->set('items.0.jumlah', 7000000)
            ->call('ajukanUMP')
            ->assertHasErrors(['catatanKekurangan' => 'required']);

        $this->assertSame('draft', $this->ump->fresh()->status);
    }

    public function test_ump_item_date_outside_tahun_anggaran_fails(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $wrongYear = ((int) $ta->tahun) - 1; // a year before the budget year

        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('tambahBaris')
            ->set('items.0.tgl', $wrongYear.'-06-15')
            ->set('items.0.rincian', 'Pengeluaran tahun lalu')
            ->set('items.0.jumlah', 500000)
            ->call('simpanDraft')
            ->assertHasErrors(['items.0.tgl']);

        $this->assertSame('draft', $this->ump->fresh()->status);
    }

    public function test_ump_item_date_within_tahun_anggaran_passes(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();

        Livewire::actingAs($this->pjUser)
            ->test(UmpDetail::class, ['id' => $this->ump->id])
            ->call('tambahBaris')
            ->set('items.0.tgl', $ta->tahun.'-06-15')
            ->set('items.0.rincian', 'Pengeluaran valid')
            ->set('items.0.jumlah', 500000)
            ->call('simpanDraft')
            ->assertHasNoErrors();
    }
}
