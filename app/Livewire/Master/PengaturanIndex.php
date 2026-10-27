<?php

namespace App\Livewire\Master;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengaturan Sistem: SIGMA LAZ Al-Azhar')]
class PengaturanIndex extends Component
{
    public const SETTING_REGISTRY = [
        // Anggaran
        ['key' => 'ambang.limit', 'label' => 'Ambang Peringatan Limit (%)', 'type' => 'number', 'default' => 80, 'group' => 'Anggaran', 'hint' => 'Persentase pemakaian efektif yang memicu status Mendekati Limit.'],
        ['key' => 'ambang.over', 'label' => 'Ambang Over Budget (%)', 'type' => 'number', 'default' => 100, 'group' => 'Anggaran', 'hint' => 'Persentase pemakaian efektif yang memicu status Over Budget.'],
        ['key' => 'kode.max_depth', 'label' => 'Kedalaman Maksimum Kode Anggaran', 'type' => 'number', 'default' => 4, 'group' => 'Anggaran', 'hint' => 'Jumlah level kedalaman pohon kode anggaran.'],
        // Pembulatan
        ['key' => 'pembulatan.kelipatan', 'label' => 'Kelipatan Pembulatan (Rp)', 'type' => 'number', 'default' => 1000, 'group' => 'Pembulatan', 'hint' => 'Nominal kelipatan pembulatan saldo UMP.'],
        ['key' => 'pembulatan.arah', 'label' => 'Arah Pembulatan', 'type' => 'select', 'options' => ['terdekat', 'atas', 'bawah'], 'default' => 'terdekat', 'group' => 'Pembulatan', 'hint' => 'Arah pembulatan: terdekat, selalu ke atas, atau selalu ke bawah.'],
        // UMP & Tenggat
        ['key' => 'ump.tenggat_hari', 'label' => 'Jatuh Tempo UMP (Hari)', 'type' => 'number', 'default' => 14, 'group' => 'UMP & Reminder', 'hint' => 'Jumlah hari dari tanggal cair hingga jatuh tempo UMP. Kosong = nonaktif.', 'nullable' => true],
        ['key' => 'ump.pengingat_hari', 'label' => 'Pengingat UMP Sebelum Jatuh Tempo (Hari)', 'type' => 'number', 'default' => 3, 'group' => 'UMP & Reminder', 'hint' => 'Kirim notifikasi pengingat H-X sebelum jatuh tempo UMP.'],
        ['key' => 'permohonan.pengingat_hari', 'label' => 'Pengingat Permohonan Tertunda (Hari)', 'type' => 'number', 'default' => 3, 'group' => 'UMP & Reminder', 'hint' => 'Kirim notifikasi jika permohonan menunggu tindakan lebih dari X hari.'],
        // Lampiran
        ['key' => 'lampiran.max_kb', 'label' => 'Ukuran Maks Lampiran (KB)', 'type' => 'number', 'default' => 5120, 'group' => 'Lampiran', 'hint' => 'Ukuran maksimum per berkas lampiran dalam kilobyte (5120 = 5 MB).'],
        // Keuangan
        ['key' => 'keuangan.mode', 'label' => 'Mode Keuangan', 'type' => 'select', 'options' => ['atas_nama', 'langsung'], 'default' => 'atas_nama', 'group' => 'Keuangan', 'hint' => '"atas_nama" = petugas input mencatat atas nama Keuangan. "langsung" = Keuangan langsung.'],
        // Notifikasi
        ['key' => 'notifikasi.email_aktif', 'label' => 'Notifikasi Email', 'type' => 'select', 'options' => ['nonaktif', 'aktif'], 'default' => 'nonaktif', 'group' => 'Notifikasi', 'hint' => 'Kirim salinan pengingat dan pemberitahuan penting ke alamat email pengguna. Memerlukan konfigurasi SMTP yang aktif di server.'],
    ];

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    protected function rules(): array
    {
        return [
            'values.ambang.limit' => ['required', 'numeric', 'min:1', 'max:100'],
            'values.ambang.over' => ['required', 'numeric', 'min:1', 'max:200'],
            'values.kode.max_depth' => ['required', 'integer', 'min:1', 'max:10'],
            'values.pembulatan.kelipatan' => ['required', 'integer', 'min:1'],
            'values.pembulatan.arah' => ['required', 'in:terdekat,atas,bawah'],
            'values.ump.tenggat_hari' => ['nullable', 'integer', 'min:1'],
            'values.ump.pengingat_hari' => ['required', 'integer', 'min:1'],
            'values.permohonan.pengingat_hari' => ['required', 'integer', 'min:1'],
            'values.lampiran.max_kb' => ['required', 'integer', 'min:100', 'max:51200'],
            'values.keuangan.mode' => ['required', 'in:atas_nama,langsung'],
            'values.notifikasi.email_aktif' => ['required', 'in:nonaktif,aktif'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'values.ambang.limit' => 'Ambang Peringatan Limit',
            'values.ambang.over' => 'Ambang Over Budget',
            'values.kode.max_depth' => 'Kedalaman Maksimum Kode Anggaran',
            'values.pembulatan.kelipatan' => 'Kelipatan Pembulatan',
            'values.pembulatan.arah' => 'Arah Pembulatan',
            'values.ump.tenggat_hari' => 'Jatuh Tempo UMP',
            'values.ump.pengingat_hari' => 'Pengingat UMP Sebelum Jatuh Tempo',
            'values.permohonan.pengingat_hari' => 'Pengingat Permohonan Tertunda',
            'values.lampiran.max_kb' => 'Ukuran Maks Lampiran',
            'values.keuangan.mode' => 'Mode Keuangan',
            'values.notifikasi.email_aktif' => 'Notifikasi Email',
        ];
    }

    public function mount(): void
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator.');
        }

        foreach (self::SETTING_REGISTRY as $entry) {
            data_set($this->values, $entry['key'], Setting::get($entry['key'], $entry['default']));
        }
    }

    public function save(): void
    {
        $this->validate();

        foreach (self::SETTING_REGISTRY as $entry) {
            $key = $entry['key'];
            $val = data_get($this->values, $key);

            if (isset($entry['nullable']) && $entry['nullable'] && ($val === '' || $val === null)) {
                Setting::set($key, null);
                data_set($this->values, $key, null);
            } else {
                if ($entry['type'] === 'number') {
                    $val = is_numeric($val) ? (int) $val : $val;
                }
                Setting::set($key, $val);
                data_set($this->values, $key, $val);
            }
        }

        session()->flash('success', 'Pengaturan berhasil disimpan.');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Pengaturan berhasil disimpan.']);
    }

    public function resetToDefaults(): void
    {
        foreach (self::SETTING_REGISTRY as $entry) {
            data_set($this->values, $entry['key'], $entry['default']);
        }
    }

    public function render(): View
    {
        $groups = [];
        foreach (self::SETTING_REGISTRY as $entry) {
            $groups[$entry['group']][] = $entry;
        }

        return view('livewire.master.pengaturan-index', [
            'groups' => $groups,
        ]);
    }
}
