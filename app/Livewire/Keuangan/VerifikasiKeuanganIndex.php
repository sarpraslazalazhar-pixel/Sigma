<?php

namespace App\Livewire\Keuangan;

use App\Services\VerifikasiKeuangan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Verifikasi Keuangan: SIGMA LAZ Al-Azhar')]
class VerifikasiKeuanganIndex extends Component
{
    public string $search = '';

    public string $filterJenis = 'semua';

    public string $filterStatus = 'belum';

    public ?string $dokumenDipilih = null;

    public string $catatan = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('verifikasi-keuangan'), 403);
    }

    public function pilih(string $type, int $id): void
    {
        Gate::authorize('verifikasi-keuangan');
        $this->resetValidation('catatan');
        $this->reset('catatan');
        $this->dokumenDipilih = "$type-$id";
    }

    public function tutupCatatan(): void
    {
        $this->resetValidation('catatan');
        $this->reset('dokumenDipilih', 'catatan');
    }

    public function verifikasi(string $type, int $id): void
    {
        app(VerifikasiKeuangan::class)->decide($type, $id, Auth::user(), 'verified', $this->catatan);
        $this->tutupCatatan();
        session()->flash('success', 'Kelengkapan dokumen terverifikasi.');
    }

    public function kembalikan(string $type, int $id): void
    {
        app(VerifikasiKeuangan::class)->decide($type, $id, Auth::user(), 'returned', $this->catatan);
        $this->tutupCatatan();
        session()->flash('success', 'Dokumen dikembalikan untuk perbaikan.');
    }

    public function batalVerifikasi(string $type, int $id): void
    {
        app(VerifikasiKeuangan::class)->decide($type, $id, Auth::user(), 'unverified');
        session()->flash('success', 'Verifikasi dibatalkan. Dokumen kembali menunggu pemeriksaan.');
    }

    public function render(VerifikasiKeuangan $verification): View
    {
        Gate::authorize('verifikasi-keuangan');

        $types = $this->filterJenis === 'semua' ? VerifikasiKeuangan::TYPES : [$this->filterJenis];
        $items = collect();

        foreach (array_intersect($types, VerifikasiKeuangan::TYPES) as $type) {
            [$label, $relations] = match ($type) {
                'permohonan' => ['Permohonan', ['unit', 'kodeAnggaran']],
                'pencairan' => ['Pencairan PPD', ['permohonan.unit', 'permohonan.kodeAnggaran']],
                'ump' => ['Laporan', ['pencairan.permohonan.unit', 'pencairan.permohonan.kodeAnggaran']],
            };
            $query = $verification->documents($type);
            $query = match ($this->filterStatus) {
                'terverifikasi' => $query->whereNotNull('verifikasi_keuangan_at'),
                'perbaikan' => $query->whereNull('verifikasi_keuangan_at')->whereNotNull('verifikasi_keuangan_catatan'),
                'semua' => $query->where(fn ($group) => $verification->active($group, $type)
                    ->orWhereNotNull('verifikasi_keuangan_at')->orWhereNotNull('verifikasi_keuangan_catatan')),
                default => $verification->pending($type),
            };
            $documents = $query->with([...$relations, 'verifikator'])->get();
            $activeIds = in_array($this->filterStatus, ['terverifikasi', 'semua'], true) && $documents->isNotEmpty()
                ? $verification->active($verification->documents($type), $type)->whereKey($documents->modelKeys())->pluck('id')->flip()
                : null;

            foreach ($documents as $document) {
                [$request, $number, $amount] = match ($type) {
                    'permohonan' => [$document, $document->no_pengajuan, $document->total_disetujui ?? $document->total_diminta],
                    'pencairan' => [$document->permohonan, $document->no_ppd, $document->jumlah],
                    'ump' => [$document->pencairan?->permohonan, $document->pencairan?->no_ppd, $document->jumlah_total],
                };
                $id = $document->id;
                $verified = $document->verifikasi_keuangan_at !== null;
                $returned = ! $verified && $document->verifikasi_keuangan_catatan !== null;
                $items->push([
                    'type' => $type,
                    'id' => $id,
                    'label' => $label,
                    'number' => $number ?? "#$id",
                    'unit' => $request?->unit?->nama,
                    'budget' => $request?->kodeAnggaran?->kode,
                    'activity' => $request?->nama_kegiatan,
                    'amount' => $amount,
                    'date' => $document->created_at,
                    'url' => $verification->sourceUrl($type, $id),
                    'note' => $document->verifikasi_keuangan_catatan,
                    'verified' => $verified,
                    'returned' => $returned,
                    'reviewer' => $document->verifikator?->name,
                    'canReview' => ! $returned && ($activeIds === null || $activeIds->has($id))
                        && $verification->mayReview($document, Auth::user()),
                    'unattributedPpd' => $type === 'pencairan' && $document->dicairkan_oleh === null,
                ]);
            }
        }

        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            $items = $items->filter(fn (array $item) => str_contains(mb_strtolower(implode(' ', [
                $item['number'], $item['unit'], $item['budget'], $item['activity'],
            ])), $term));
        }

        return view('livewire.keuangan.verifikasi-keuangan-index', [
            'items' => $items->sortByDesc('date'),
        ]);
    }
}
