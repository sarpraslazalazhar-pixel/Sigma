<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\KodeAnggaran;
use App\Models\Lampiran;
use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Ump;
use App\Models\UmpItem;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PencairanUmpModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Permohonan $permohonan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->user = User::where('username', 'subakti')->firstOrFail();

        $ta = TahunAnggaran::where('status', 'aktif')->firstOrFail();
        $unit = Unit::where('kode', 'UNT-SEK-KLM')->firstOrFail();
        $sumberDana = SumberDana::firstOrFail();

        $kodeAnggaran = KodeAnggaran::create([
            'tahun_anggaran_id' => $ta->id,
            'kode' => '99',
            'nama' => 'Tes PPD dan UMP',
            'depth' => 1,
            'plafon' => 10000000,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9001',
            'unit_id' => $unit->id,
            'user_id' => $this->user->id,
            'kode_anggaran_id' => $kodeAnggaran->id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Tes PPD dan UMP',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $sumberDana->id,
            'status' => 'dicairkan',
        ]);
    }

    public function test_pencairan_creation_and_relations(): void
    {
        $pencairan = $this->makePencairan('PPD-2026-0001');

        $this->assertDatabaseHas('pencairan', [
            'permohonan_id' => $this->permohonan->id,
            'no_ppd' => 'PPD-2026-0001',
            'jumlah' => 5000000,
        ]);

        $this->assertInstanceOf(Carbon::class, $pencairan->tgl_cair);
        $this->assertSame('2026-10-15', $pencairan->tgl_cair->toDateString());
        $this->assertSame(5000000, $pencairan->jumlah);
        $this->assertFalse($pencairan->perlu_verifikasi_keuangan);

        $this->assertTrue($pencairan->permohonan->is($this->permohonan));
        $this->assertTrue($this->permohonan->pencairan->is($pencairan));

        $lampiran = $pencairan->lampiran()->create([
            'jenis' => 'bukti',
            'path' => 'pencairan/ppd-2026-0001.pdf',
            'original_name' => 'ppd.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertCount(1, $pencairan->lampiran);
        $this->assertTrue($pencairan->lampiran->first()->is($lampiran));
        $this->assertTrue($lampiran->attachable->is($pencairan));
    }

    public function test_ump_creation_and_relations(): void
    {
        $pencairan = $this->makePencairan('PPD-2026-0002');

        $ump = Ump::create([
            'pencairan_id' => $pencairan->id,
            'tgl_terima' => '2026-10-16',
            'tujuan' => 'Santunan Yatim Piatu',
            'pj_id' => $this->user->id,
            'jumlah_total' => 5000000,
            'total_penggunaan' => 4900000,
            'saldo' => 100000,
            'pembulatan' => 0,
            'kekurangan' => 0,
            'status' => 'draft',
            'jatuh_tempo' => '2026-10-30',
            'perlu_verifikasi_keuangan' => true,
        ]);

        $this->assertDatabaseHas('ump', [
            'pencairan_id' => $pencairan->id,
            'pj_id' => $this->user->id,
            'jumlah_total' => 5000000,
        ]);

        $this->assertInstanceOf(Carbon::class, $ump->tgl_terima);
        $this->assertSame('2026-10-16', $ump->tgl_terima->toDateString());
        $this->assertSame('2026-10-30', $ump->jatuh_tempo->toDateString());
        $this->assertSame(5000000, $ump->jumlah_total);
        $this->assertTrue($ump->perlu_verifikasi_keuangan);

        $this->assertTrue($ump->pencairan->is($pencairan));
        $this->assertTrue($ump->pj->is($this->user));
        $this->assertTrue($pencairan->ump->is($ump));
        $this->assertTrue($this->user->ump->contains($ump));

        $lampiran = $ump->lampiran()->create([
            'jenis' => 'bukti_tf',
            'path' => 'ump/ump-2026-0001.pdf',
            'original_name' => 'bukti_tf.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
            'uploaded_by' => $this->user->id,
        ]);

        $approval = $ump->approvals()->create([
            'siklus' => 1,
            'tahap' => 'keuangan',
            'keputusan' => 'setuju',
            'approver_id' => $this->user->id,
            'signer_name' => $this->user->name,
            'signed_at' => now(),
        ]);

        $this->assertCount(1, $ump->lampiran);
        $this->assertTrue($ump->lampiran->first()->is($lampiran));
        $this->assertCount(1, $ump->approvals);
        $this->assertTrue($ump->approvals->first()->is($approval));
        $this->assertTrue($approval->approvable->is($ump));
        $this->assertInstanceOf(Approval::class, $ump->approvals->first());
        $this->assertInstanceOf(Lampiran::class, $ump->lampiran->first());
    }

    public function test_ump_item_creation_and_relation(): void
    {
        $ump = $this->makeUmp();

        $item = UmpItem::create([
            'ump_id' => $ump->id,
            'tgl' => '2026-10-17',
            'rincian' => 'Pembelian paket sembako',
            'no_bukti' => 'BKT-001',
            'jumlah' => 1500000,
        ]);

        $this->assertDatabaseHas('ump_items', [
            'ump_id' => $ump->id,
            'jumlah' => 1500000,
        ]);

        $this->assertInstanceOf(Carbon::class, $item->tgl);
        $this->assertSame('2026-10-17', $item->tgl->toDateString());
        $this->assertSame(1500000, $item->jumlah);
        $this->assertTrue($item->ump->is($ump));
        $this->assertCount(1, $ump->items);
        $this->assertTrue($ump->items->first()->is($item));
    }

    public function test_unique_constraint_duplicate_no_ppd_and_permohonan_id_fail(): void
    {
        $this->makePencairan('PPD-2026-0001');

        $otherPermohonan = Permohonan::create([
            'no_pengajuan' => 'KLM-PMH-2026-9002',
            'unit_id' => $this->permohonan->unit_id,
            'user_id' => $this->permohonan->user_id,
            'kode_anggaran_id' => $this->permohonan->kode_anggaran_id,
            'program' => 'Program Tes',
            'nama_kegiatan' => 'Kegiatan Tes Kedua',
            'tgl_mulai' => now()->toDateString(),
            'sumber_dana_id' => $this->permohonan->sumber_dana_id,
            'status' => 'dicairkan',
        ]);

        try {
            Pencairan::create([
                'permohonan_id' => $otherPermohonan->id,
                'no_ppd' => 'PPD-2026-0001',
                'tgl_cair' => '2026-10-15',
                'jumlah' => 1000000,
            ]);
            $this->fail('Duplikat no_ppd seharusnya gagal pada constraint unique.');
        } catch (QueryException) {
        }

        try {
            Pencairan::create([
                'permohonan_id' => $this->permohonan->id,
                'no_ppd' => 'PPD-2026-0002',
                'tgl_cair' => '2026-10-15',
                'jumlah' => 1000000,
            ]);
            $this->fail('Duplikat permohonan_id seharusnya gagal pada constraint unique.');
        } catch (QueryException) {
        }

        $this->assertSame(1, Pencairan::count());
    }

    protected function makePencairan(string $noPpd, ?Permohonan $permohonan = null): Pencairan
    {
        return Pencairan::create([
            'permohonan_id' => ($permohonan ?? $this->permohonan)->id,
            'no_ppd' => $noPpd,
            'tgl_cair' => '2026-10-15',
            'jumlah' => 5000000,
            'status' => 'dicairkan',
            'perlu_verifikasi_keuangan' => false,
        ]);
    }

    protected function makeUmp(): Ump
    {
        return Ump::create([
            'pencairan_id' => $this->makePencairan('PPD-2026-0003')->id,
            'tgl_terima' => '2026-10-16',
            'tujuan' => 'Santunan Yatim Piatu',
            'pj_id' => $this->user->id,
            'jumlah_total' => 5000000,
            'status' => 'draft',
            'jatuh_tempo' => '2026-10-30',
        ]);
    }
}
