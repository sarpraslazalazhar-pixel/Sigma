<?php

namespace App\Livewire\Master;

use App\Models\Leveling;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Manajemen Pengguna & Leveling: SIGMA LAZ Al-Azhar')]
class UserIndex extends Component
{
    public string $search = '';

    public string $roleFilter = '';

    public string $unitFilter = '';

    public string $levelingFilter = '';

    public string $statusFilter = '';

    // Modal Form Create/Edit
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $form_name = '';

    public string $form_username = '';

    public string $form_email = '';

    public string $form_password = '';

    public string $form_role = 'unit';

    public ?int $form_unit_id = null;

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
            'form_leveling_id' => ['nullable', 'exists:levelings,id'],
            'form_jabatan' => ['nullable', 'string', 'max:100'],
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

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $before = $user->only(['name', 'username', 'email', 'role', 'unit_id', 'leveling_id', 'jabatan', 'is_petugas_input', 'is_active']);
            $user->update($data);
            $logger->log('user.updated', $user, $before, $user->only(['name', 'username', 'email', 'role', 'unit_id', 'leveling_id', 'jabatan', 'is_petugas_input', 'is_active']));
            session()->flash('success', "Akun pengguna {$user->name} berhasil diperbarui.");
        } else {
            $user = User::create($data);
            $logger->log('user.created', $user, null, $user->only(['name', 'username', 'email', 'role', 'unit_id', 'leveling_id', 'jabatan', 'is_petugas_input', 'is_active']));
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

    public function render()
    {
        $query = User::with(['unit', 'leveling'])
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
                $q->where('unit_id', $this->unitFilter);
            })
            ->when($this->levelingFilter, function ($q) {
                $q->where('leveling_id', $this->levelingFilter);
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('is_active', (bool) $this->statusFilter);
            })
            ->orderBy('role', 'asc')
            ->orderBy('name', 'asc');

        $users = $query->get();
        $units = Unit::orderBy('parent_id')->orderBy('kode')->get();
        $levelings = Leveling::orderBy('urutan')->get();

        return view('livewire.master.user-index', [
            'users' => $users,
            'units' => $units,
            'levelings' => $levelings,
        ]);
    }
}
