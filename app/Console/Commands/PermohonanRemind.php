<?php

namespace App\Console\Commands;

use App\Models\Permohonan;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PermohonanRemind extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permohonan:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat untuk permohonan dana yang tertunda di setiap tahap.';

    public function handle(): int
    {
        $pengingatHari = Setting::get('permohonan.pengingat_hari');
        if ($pengingatHari === null || (int) $pengingatHari === 0) {
            $this->info('Fitur pengingat permohonan nonaktif (permohonan.pengingat_hari kosong atau 0).');

            return Command::SUCCESS;
        }

        $pengingatHari = (int) $pengingatHari;
        $today = Carbon::today();

        $permohonans = Permohonan::with(['user', 'atasan', 'unit'])
            ->whereIn('status', ['diajukan', 'diketahui_kadiv', 'disetujui_keuangan'])
            ->get();

        $labelMap = [
            'diajukan' => 'Pemeriksaan Atasan',
            'diketahui_kadiv' => 'Verifikasi Keuangan',
            'disetujui_keuangan' => 'Pencairan Dana',
        ];

        $count = 0;
        foreach ($permohonans as $permohonan) {
            $updatedAt = $permohonan->updated_at ?? $permohonan->created_at ?? $today;
            $startOfDay = $updatedAt->copy()->startOfDay();

            $hariIdle = $today->isBefore($startOfDay) ? 0 : (int) $startOfDay->diffInDays($today);

            if ($hariIdle < $pengingatHari) {
                continue;
            }

            $labelTahap = $labelMap[$permohonan->status] ?? $permohonan->status;

            $recipients = collect();
            if ($permohonan->status === 'diajukan') {
                $approver = $permohonan->atasan;
                if (! $approver && $permohonan->unit_id) {
                    $unit = Unit::find($permohonan->unit_id);
                    $divisiId = $unit?->getDivisiId();
                    if ($divisiId) {
                        $approver = User::where('is_active', true)
                            ->where('unit_id', $divisiId)
                            ->whereHas('leveling', fn ($l) => $l->where('urutan', '>=', 3))
                            ->first();
                    }
                    if (! $approver) {
                        $approver = $unit?->getManager();
                    }
                }

                if ($approver && $approver->is_active) {
                    $recipients->push($approver);
                }
            } else {
                $keuanganUsers = User::where('is_active', true)
                    ->where(function ($q) {
                        $q->where('role', 'keuangan')
                            ->orWhere('role', 'admin');
                    })
                    ->get();

                $recipients = $keuanganUsers;
            }

            if ($recipients->isEmpty()) {
                continue;
            }

            $dedupKey = "pengingat_permohonan_{$permohonan->id}_{$permohonan->status}";
            $payload = [
                'judul' => "Permohonan Dana Tertunda {$hariIdle} Hari",
                'pesan' => "Permohonan {$permohonan->no_pengajuan} dari {$permohonan->user?->name} sudah {$hariIdle} hari menunggu tindakan di tahap {$labelTahap}.",
                'url' => route('permohonan.detail', $permohonan->id),
                'jenis' => 'permohonan',
                'entity_id' => $permohonan->id,
            ];

            foreach ($recipients as $recipient) {
                NotificationService::kirim(
                    $recipient,
                    'permohonan_tertunda',
                    $payload,
                    $dedupKey
                );
                $count++;
            }
        }

        $this->info("Pengingat permohonan selesai diproses. Notifikasi terkirim/terverifikasi: {$count}.");

        return Command::SUCCESS;
    }
}
