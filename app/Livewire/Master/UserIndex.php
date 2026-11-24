<?php

namespace App\Livewire\Master;

use App\Models\Leveling;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Manajemen Pengguna & Leveling: SIGMA LAZ Al-Azhar')]
class UserIndex extends Component
{
    public string $search = '';

    public string $roleFilter = '';

    #[Url]
    public string $unitFilter = '';

    public string $levelingFilter = '';

    public string $statusFilter = '';

    public string $viewMode = 'hierarchy'; // 'hierarchy' | 'alphabetical'

    // Modal Form Create/Edit
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $form_name = '';

    public string $form_username = '';

    public string $form_email = '';

    public string $form_password = '';

    public string $form_role = 'unit';

    public ?int $form_unit_id = null;

    public ?int $form_atasan_id = null;

    public ?int $form_leveling_id = null;

    public string $form_jabatan = '';

    public string $form_status_kepegawaian = 'Tetap';

    public bool $form_is_petugas_input = false;

    public bool $form_is_active = true;

    // Modal Reset Password
    public bool $showResetPasswordModal = false;

    public ?int $resetUserId = null;

    public string $resetUserName = '';

    public string $resetNewPassword = '';

    public function mount(): void
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator IT.');
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['hierarchy', 'alphabetical'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form_name = '';
        $this->form_username = '';
        $this->form_email = '';
        $this->form_password = '';
        $this->form_role = 'unit';
        $this->form_unit_id = null;
        $this->form_atasan_id = null;
        $this->form_leveling_id = null;
        $this->form_jabatan = '';
        $this->form_status_kepegawaian = 'Tetap';
        $this->form_is_petugas_input = false;
        $this->form_is_active = true;

        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->form_name = $user->name;
        $this->form_username = $user->username;
        $this->form_email = $user->email;
        $this->form_password = '';
        $this->form_role = $user->role;
        $this->form_unit_id = $user->unit_id;
        $this->form_atasan_id = $user->atasan_id;
        $this->form_leveling_id = $user->leveling_id;
        $this->form_jabatan = $user->jabatan ?? '';
        $this->form_status_kepegawaian = $user->status_kepegawaian ?? 'Tetap';
        $this->form_is_petugas_input = (bool) $user->is_petugas_input;
        $this->form_is_active = (bool) $user->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'form_name' => ['required', 'string', 'max:255'],
            'form_username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($this->editingId),
            ],
            'form_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'form_role' => ['required', 'in:admin,unit,keuangan,viewer'],
            'form_unit_id' => ['nullable', 'exists:units,id'],
            'form_atasan_id' => [
                'nullable',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value || ! $this->editingId) {
                        return;
                    }

                    $targetId = (int) $value;
                    if ($targetId === $this->editingId) {
                        $fail('Pengguna tidak dapat menjadi atasan langsung bagi dirinya sendiri.');

                        return;
                    }

                    $editingUser = User::find($this->editingId);
                    if ($editingUser && in_array($targetId, $editingUser->getDescendantIds(), true)) {
                        $fail('Atasan langsung tidak boleh berasal dari bawahan pengguna ini.');
                    }
                },
            ],
            'form_leveling_id' => ['nullable', 'exists:levelings,id'],
            'form_jabatan' => ['nullable', 'string', 'max:255'],
            'form_status_kepegawaian' => ['nullable', 'in:Tetap,Kontrak,Relawan'],
            'form_is_petugas_input' => ['boolean'],
            'form_is_active' => ['boolean'],
        ];

        if (! $this->editingId) {
            $rules['form_password'] = ['required', 'string', 'min:6'];
        } else {
            $rules['form_password'] = ['nullable', 'string', 'min:6'];
        }

        $this->validate($rules);

        $data = [
            'name' => trim($this->form_name),
            'username' => trim($this->form_username),
            'email' => trim($this->form_email),
            'role' => $this->form_role,
            'unit_id' => $this->form_unit_id ?: null,
            'atasan_id' => $this->form_atasan_id ?: null,
            'leveling_id' => $this->form_leveling_id ?: null,
            'jabatan' => trim($this->form_jabatan) ?: null,
            'status_kepegawaian' => $this->form_status_kepegawaian,
            'is_petugas_input' => $this->form_is_petugas_input,
            'is_active' => $this->form_is_active,
        ];

        if (! empty($this->form_password)) {
            $data['password'] = Hash::make($this->form_password);
        }

        $logger = new AuditLogger;
        $auditFields = ['name', 'username', 'email', 'role', 'unit_id', 'atasan_id', 'leveling_id', 'jabatan', 'is_petugas_input', 'is_active'];

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $before = $user->only($auditFields);
            $user->update($data);
            $logger->log('user.updated', $user, $before, $user->only($auditFields));
            session()->flash('success', "Akun pengguna {$user->name} berhasil diperbarui.");
        } else {
            $user = User::create($data);
            $logger->log('user.created', $user, null, $user->only($auditFields));
            session()->flash('success', "Pengguna {$user->name} ({$user->username}) berhasil didaftarkan.");
        }

        $this->showModal = false;
    }

    public function openResetPasswordModal(int $id): void
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $this->resetUserId = $user->id;
        $this->resetUserName = $user->name.' ('.$user->username.')';
        $this->resetNewPassword = '';
        $this->showResetPasswordModal = true;
    }

    public function saveResetPassword(): void
    {
        $this->validate([
            'resetNewPassword' => ['required', 'string', 'min:6'],
        ]);

        $user = User::findOrFail($this->resetUserId);
        $user->update([
            'password' => Hash::make($this->resetNewPassword),
        ]);

        (new AuditLogger)->log('user.password_reset', $user, null, ['status' => 'password_reset_by_admin']);

        $this->showResetPasswordModal = false;
        session()->flash('success', "Kata sandi untuk {$user->username} berhasil diatur ulang.");
    }

    public function toggleActive(int $id): void
    {
        // Cegah admin menonaktifkan akunnya sendiri saat sedang login
        if ($id === Auth::id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');

            return;
        }

        $user = User::findOrFail($id);
        $before = ['is_active' => $user->is_active];
        $user->update(['is_active' => ! $user->is_active]);

        (new AuditLogger)->log('user.toggle_active', $user, $before, ['is_active' => $user->is_active]);

        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('success', "Akun {$user->username} berhasil {$statusStr}.");
    }

    /**
     * Susun pengelompokan hierarkis (Divisi Induk → Unit Kerja) untuk tampilan daftar pengguna.
     *
     * @param  Collection<int, User>  $sortedUsers
     * @param  Collection<int, Unit>  $sortedUnits
     * @return Collection<int, array{
     *     unit: ?Unit,
     *     divisi: ?Unit,
     *     show_divisi_banner: bool,
     *     divisi_total: int,
     *     users: Collection<int, User>,
     * }>
     */
    private function buildHierarchyGroups(Collection $sortedUsers, Collection $sortedUnits): Collection
    {
        $unitsById = $sortedUnits->keyBy('id');
        $grouped = $sortedUsers->groupBy(fn (User $u) => $u->unit_id ?? 0);

        $divisiCounts = [];
        foreach ($sortedUsers as $user) {
            $divisiId = $user->unit?->getDivisiId() ?? 0;
            $divisiCounts[$divisiId] = ($divisiCounts[$divisiId] ?? 0) + 1;
        }

        $orderedUnitKeys = [];
        foreach ($sortedUnits as $unit) {
            if ($grouped->has($unit->id)) {
                $orderedUnitKeys[] = $unit->id;
            }
        }
        foreach ($grouped->keys() as $key) {
            if (! in_array($key, $orderedUnitKeys, true)) {
                $orderedUnitKeys[] = $key;
            }
        }

        $sections = collect();
        $lastDivisiId = -1;

        foreach ($orderedUnitKeys as $unitKey) {
            /** @var Unit|null $unit */
            $unit = $unitKey ? $unitsById->get($unitKey) : null;
            $divisi = $unit
                ? ($unit->tipe === 'divisi' ? $unit : ($unit->parent ?? $unitsById->get($unit->parent_id)))
                : null;
            $divisiId = $divisi?->id ?? 0;

            $showDivisiBanner = $divisiId !== $lastDivisiId;
            $lastDivisiId = $divisiId;

            $sections->push([
                'unit' => $unit,
                'divisi' => $divisi,
                'show_divisi_banner' => $showDivisiBanner,
                'divisi_total' => $divisiCounts[$divisiId] ?? 0,
                'users' => $grouped->get($unitKey, collect()),
            ]);
        }

        return $sections;
    }

    /**
     * Siapkan kandidat atasan langsung untuk modal Create/Edit User.
     *
     * @param  Collection<int, Unit>  $sortedUnits
     * @return Collection<int, array{id: int, name: string, sub: string}>
     */
    private function buildAtasanOptions(Collection $sortedUnits): Collection
    {
        $excludedIds = [];
        if ($this->editingId) {
            $excludedIds[] = $this->editingId;
            $editingUser = User::find($this->editingId);
            if ($editingUser) {
                $excludedIds = array_merge($excludedIds, $editingUser->getDescendantIds());
            }
        }

        $selectedUnit = $this->form_unit_id ? $sortedUnits->firstWhere('id', $this->form_unit_id) : null;
        $selectedDivisiId = $selectedUnit?->getDivisiId();

        $candidates = User::with(['unit.parent', 'leveling'])
            ->where('is_active', true)
            ->when($excludedIds !== [], fn ($q) => $q->whereNotIn('id', $excludedIds))
            ->get();

        return $candidates
            ->sort(function (User $a, User $b) use ($selectedDivisiId): int {
                $prioA = $this->atasanPriority($a, $this->form_unit_id, $selectedDivisiId);
                $prioB = $this->atasanPriority($b, $this->form_unit_id, $selectedDivisiId);
                if ($prioA !== $prioB) {
                    return $prioA <=> $prioB;
                }

                $lvlA = (int) ($a->leveling?->urutan ?? 0);
                $lvlB = (int) ($b->leveling?->urutan ?? 0);
                if ($lvlA !== $lvlB) {
                    return $lvlB <=> $lvlA;
                }

                return strnatcasecmp($a->name, $b->name);
            })
            ->values()
            ->map(function (User $cand): array {
                $parts = array_filter([
                    $cand->jabatan ?: $cand->leveling?->nama,
                    $cand->unit?->nama,
                ]);

                return [
                    'id' => $cand->id,
                    'name' => $cand->name,
                    'sub' => implode(' • ', $parts),
                ];
            });
    }

    private function atasanPriority(User $candidate, ?int $selectedUnitId, ?int $selectedDivisiId): int
    {
        if ($selectedUnitId !== null && $candidate->unit_id === $selectedUnitId) {
            return 0;
        }

        if ($selectedDivisiId !== null && $candidate->unit?->getDivisiId() === $selectedDivisiId) {
            return 1;
        }

        return 2;
    }

    public function render(): View
    {
        $query = User::with(['unit.parent', 'leveling', 'atasan'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('jabatan', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter, function ($q) {
                $q->where('role', $this->roleFilter);
            })
            ->when($this->unitFilter, function ($q) {
                $selectedUnit = Unit::find($this->unitFilter);
                if ($selectedUnit && $selectedUnit->tipe === 'divisi') {
                    $q->whereIn('unit_id', $selectedUnit->getSameDivisiUnitIds());
                } else {
                    $q->where('unit_id', $this->unitFilter);
                }
            })
            ->when($this->levelingFilter !== '', function ($q) {
                if ($this->levelingFilter === 'none') {
                    $q->whereNull('leveling_id');
                } else {
                    $q->where('leveling_id', $this->levelingFilter);
                }
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('is_active', (bool) $this->statusFilter);
            });

        $sortedUnits = Unit::sortHierarchically(Unit::with('parent')->get());
        $rawUsers = $query->get();

        if ($this->viewMode === 'alphabetical') {
            $users = $rawUsers
                ->sortBy(fn (User $u) => [$u->is_active ? 0 : 1, mb_strtolower($u->name)])
                ->values();
            $userGroups = collect();
        } else {
            $users = User::sortHierarchically($rawUsers, $sortedUnits);
            $userGroups = $this->buildHierarchyGroups($users, $sortedUnits);
        }

        $unitOptions = Unit::formatDropdownOptions($sortedUnits);
        $levelings = Leveling::orderBy('urutan')->get();
        $atasanOptions = $this->showModal ? $this->buildAtasanOptions($sortedUnits) : collect();

        return view('livewire.master.user-index', [
            'users' => $users,
            'userGroups' => $userGroups,
            'units' => $unitOptions,
            'levelings' => $levelings,
            'atasanOptions' => $atasanOptions,
        ]);
    }
}
