<?php

namespace App\Livewire\Keuangan;

use App\Models\Pencairan;
use App\Models\Permohonan;
use App\Models\Ump;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Verifikasi Keuangan: SIGMA LAZ Al-Azhar')]
class VerifikasiKeuanganIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterJenis = 'semua';

    public string $filterStatus = 'belum';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterJenis(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function verifikasi(string $type, int $id): void
    {
        abort_unless(in_array(Auth::user()?->role, ['admin', 'keuangan']), 403);

        $entity = match ($type) {
            'permohonan' => Permohonan::findOrFail($id),
            'pencairan' => Pencairan::findOrFail($id),
            'ump' => Ump::findOrFail($id),
            default => abort(404),
        };

        $before = $entity->toArray();
        $entity->update([
            'verifikasi_keuangan_at' => now(),
            'verifikasi_keuangan_oleh' => Auth::id(),
        ]);

        (new AuditLogger)->log('verifikasi_keuangan.verified', $entity, $before, $entity->fresh()->toArray());

        session()->flash('success', "Dokumen {$type} berhasil diverifikasi.");
    }

    public function batalVerifikasi(string $type, int $id): void
    {
        abort_unless(in_array(Auth::user()?->role, ['admin', 'keuangan']), 403);

        $entity = match ($type) {
            'permohonan' => Permohonan::findOrFail($id),
            'pencairan' => Pencairan::findOrFail($id),
            'ump' => Ump::findOrFail($id),
            default => abort(404),
        };

        $before = $entity->toArray();
        $entity->update([
            'verifikasi_keuangan_at' => null,
            'verifikasi_keuangan_oleh' => null,
        ]);

        (new AuditLogger)->log('verifikasi_keuangan.unverified', $entity, $before, $entity->fresh()->toArray());

        session()->flash('success', "Status verifikasi dokumen {$type} berhasil dibatalkan.");
    }

    public function render()
    {
        abort_unless(in_array(Auth::user()?->role, ['admin', 'keuangan']), 403);

        $countPendingPermohonan = Permohonan::where('perlu_verifikasi_keuangan', true)->whereNull('verifikasi_keuangan_at')->count();
        $countPendingPencairan = Pencairan::where('perlu_verifikasi_keuangan', true)->whereNull('verifikasi_keuangan_at')->count();
        $countPendingUmp = Ump::where('perlu_verifikasi_keuangan', true)->whereNull('verifikasi_keuangan_at')->count();
        $totalPending = $countPendingPermohonan + $countPendingPencairan + $countPendingUmp;

        $items = collect();

        if (in_array($this->filterJenis, ['semua', 'permohonan'])) {
            $permohonanQuery = Permohonan::where('perlu_verifikasi_keuangan', true)
                ->with(['unit', 'kodeAnggaran', 'user', 'verifikator']);

            if ($this->filterStatus === 'belum') {
                $permohonanQuery->whereNull('verifikasi_keuangan_at');
            } elseif ($this->filterStatus === 'terverifikasi') {
                $permohonanQuery->whereNotNull('verifikasi_keuangan_at');
            }

            foreach ($permohonanQuery->get() as $p) {
                $items->push([
                    'type' => 'permohonan',
                    'id' => $p->id,
                    'no_dokumen' => $p->no_pengajuan,
                    'jenis' => 'Permohonan',
                    'unit' => $p->unit?->nama ?? '-',
                    'kode_anggaran' => $p->kodeAnggaran?->kode ?? '-',
                    'nama_pos' => $p->kodeAnggaran?->nama ?? '-',
                    'program' => $p->program,
                    'nominal' => $p->total_disetujui ?? $p->total_diminta ?? 0,
                    'dicatat_oleh' => $p->user?->name ?? $p->user?->username ?? '-',
                    'tgl_catat' => $p->created_at,
                    'is_verified' => $p->verifikasi_keuangan_at !== null,
                    'verified_at' => $p->verifikasi_keuangan_at,
                    'verifikator' => $p->verifikator?->name ?? $p->verifikator?->username,
                ]);
            }
        }

        if (in_array($this->filterJenis, ['semua', 'pencairan'])) {
            $pencairanQuery = Pencairan::where('perlu_verifikasi_keuangan', true)
                ->with(['permohonan.unit', 'permohonan.kodeAnggaran', 'permohonan.user', 'verifikator']);

            if ($this->filterStatus === 'belum') {
                $pencairanQuery->whereNull('verifikasi_keuangan_at');
            } elseif ($this->filterStatus === 'terverifikasi') {
                $pencairanQuery->whereNotNull('verifikasi_keuangan_at');
            }

            foreach ($pencairanQuery->get() as $c) {
                $items->push([
                    'type' => 'pencairan',
                    'id' => $c->id,
                    'no_dokumen' => $c->no_ppd,
                    'jenis' => 'PPD (Pencairan)',
                    'unit' => $c->permohonan?->unit?->nama ?? '-',
                    'kode_anggaran' => $c->permohonan?->kodeAnggaran?->kode ?? '-',
                    'nama_pos' => $c->permohonan?->kodeAnggaran?->nama ?? '-',
                    'program' => $c->permohonan?->program ?? '-',
                    'nominal' => $c->jumlah,
                    'dicatat_oleh' => $c->permohonan?->user?->name ?? $c->permohonan?->user?->username ?? '-',
                    'tgl_catat' => $c->created_at,
                    'is_verified' => $c->verifikasi_keuangan_at !== null,
                    'verified_at' => $c->verifikasi_keuangan_at,
                    'verifikator' => $c->verifikator?->name ?? $c->verifikator?->username,
                ]);
            }
        }

        if (in_array($this->filterJenis, ['semua', 'ump'])) {
            $umpQuery = Ump::where('perlu_verifikasi_keuangan', true)
                ->with(['pencairan.permohonan.unit', 'pencairan.permohonan.kodeAnggaran', 'pj', 'verifikator']);

            if ($this->filterStatus === 'belum') {
                $umpQuery->whereNull('verifikasi_keuangan_at');
            } elseif ($this->filterStatus === 'terverifikasi') {
                $umpQuery->whereNotNull('verifikasi_keuangan_at');
            }

            foreach ($umpQuery->get() as $u) {
                $items->push([
                    'type' => 'ump',
                    'id' => $u->id,
                    'no_dokumen' => $u->pencairan?->no_ppd ? 'UMP ('.$u->pencairan->no_ppd.')' : 'UMP #'.$u->id,
                    'jenis' => 'UMP',
                    'unit' => $u->pencairan?->permohonan?->unit?->nama ?? '-',
                    'kode_anggaran' => $u->pencairan?->permohonan?->kodeAnggaran?->kode ?? '-',
                    'nama_pos' => $u->pencairan?->permohonan?->kodeAnggaran?->nama ?? '-',
                    'program' => $u->tujuan,
                    'nominal' => $u->jumlah_total,
                    'dicatat_oleh' => $u->pj?->name ?? $u->pj?->username ?? '-',
                    'tgl_catat' => $u->created_at,
                    'is_verified' => $u->verifikasi_keuangan_at !== null,
                    'verified_at' => $u->verifikasi_keuangan_at,
                    'verifikator' => $u->verifikator?->name ?? $u->verifikator?->username,
                ]);
            }
        }

        if (trim($this->search) !== '') {
            $term = strtolower(trim($this->search));
            $items = $items->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['no_dokumen']), $term)
                    || str_contains(strtolower($item['unit']), $term)
                    || str_contains(strtolower($item['kode_anggaran']), $term)
                    || str_contains(strtolower($item['nama_pos']), $term)
                    || str_contains(strtolower($item['dicatat_oleh']), $term);
            });
        }

        $sortedItems = $items->sortByDesc('tgl_catat')->values();

        return view('livewire.keuangan.verifikasi-keuangan-index', [
            'items' => $sortedItems,
            'totalPending' => $totalPending,
            'countPendingPermohonan' => $countPendingPermohonan,
            'countPendingPencairan' => $countPendingPencairan,
            'countPendingUmp' => $countPendingUmp,
        ]);
    }
}
