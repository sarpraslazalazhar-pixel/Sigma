<?php

namespace App\Livewire\Master;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit Trail: SIGMA LAZ Al-Azhar')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $userFilter = '';

    public bool $showDiffModal = false;

    public ?int $selectedAuditId = null;

    public function mount(): void
    {
        if (! Auth::check() || ! in_array(Auth::user()->role, ['admin', 'keuangan'])) {
            abort(403, 'Akses terbatas untuk Administrator atau Petugas Keuangan.');
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'actionFilter', 'dateFrom', 'dateTo', 'userFilter'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'actionFilter', 'dateFrom', 'dateTo', 'userFilter']);
        $this->resetPage();
    }

    public function openDiff(int $id): void
    {
        $this->selectedAuditId = $id;
        $this->showDiffModal = true;
    }

    public function closeDiffModal(): void
    {
        $this->showDiffModal = false;
        $this->selectedAuditId = null;
    }

    public function getDiffRows(?AuditLog $log): array
    {
        if (! $log) {
            return [];
        }

        $before = is_array($log->before) ? $log->before : [];
        $after = is_array($log->after) ? $log->after : [];

        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($allKeys);

        $rows = [];
        foreach ($allKeys as $key) {
            $hasBefore = array_key_exists($key, $before);
            $hasAfter = array_key_exists($key, $after);
            $oldVal = $hasBefore ? $before[$key] : null;
            $newVal = $hasAfter ? $after[$key] : null;

            if (! $hasBefore && $hasAfter) {
                $status = 'added';
            } elseif ($hasBefore && ! $hasAfter) {
                $status = 'removed';
            } elseif ($oldVal !== $newVal) {
                $status = 'modified';
            } else {
                $status = 'unchanged';
            }

            $rows[] = [
                'key' => $key,
                'old' => $oldVal,
                'new' => $newVal,
                'status' => $status,
            ];
        }

        return $rows;
    }

    public function getActionBadgeClass(string $action): string
    {
        $act = strtolower($action);
        if (str_contains($act, 'create') || str_contains($act, 'sahkan') || str_contains($act, 'tambah') || str_contains($act, 'aktif')) {
            return 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }
        if (str_contains($act, 'update') || str_contains($act, 'ubah') || str_contains($act, 'edit')) {
            return 'bg-blue-50 text-blue-800 border-blue-200';
        }
        if (str_contains($act, 'hapus') || str_contains($act, 'delete') || str_contains($act, 'nonaktif') || str_contains($act, 'batal') || str_contains($act, 'tutup')) {
            return 'bg-amber-50 text-amber-800 border-amber-200';
        }

        return 'bg-slate-100 text-slate-700 border-slate-200';
    }

    public function render(): View
    {
        $query = AuditLog::with('user')
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('action', 'like', $term)
                        ->orWhere('entity_type', 'like', $term)
                        ->orWhere('entity_id', 'like', $term);
                });
            })
            ->when($this->actionFilter !== '', function ($q) {
                $q->where('action', 'like', '%'.trim($this->actionFilter).'%');
            })
            ->when($this->userFilter !== '', function ($q) {
                $q->where('user_id', $this->userFilter);
            })
            ->when($this->dateFrom !== '', function ($q) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo !== '', function ($q) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('id', 'desc');

        $logs = $query->paginate(15);

        $users = User::orderBy('name', 'asc')->get(['id', 'name', 'username']);

        $selectedLog = $this->selectedAuditId
            ? AuditLog::with('user')->find($this->selectedAuditId)
            : null;

        $diffRows = $this->getDiffRows($selectedLog);

        return view('livewire.master.audit-log-index', [
            'logs' => $logs,
            'users' => $users,
            'selectedLog' => $selectedLog,
            'diffRows' => $diffRows,
        ]);
    }
}
