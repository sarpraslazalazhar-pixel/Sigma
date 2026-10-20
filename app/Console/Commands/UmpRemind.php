<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Ump;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UmpRemind extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ump:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi pengingat jatuh tempo UMP yang mendekati atau melewati tenggat.';

    public function handle(): int
    {
        $tenggatHari = Setting::get('ump.tenggat_hari');
        if ($tenggatHari === null || (int) $tenggatHari === 0) {
            $this->info('Fitur tenggat UMP nonaktif (ump.tenggat_hari kosong atau 0).');

            return Command::SUCCESS;
        }

        $today = Carbon::today();

        $umps = Ump::with(['pj', 'pencairan.permohonan.user'])
            ->whereNotIn('status', ['selesai', 'dibatalkan'])
            ->whereNotNull('jatuh_tempo')
            ->get();

        $count = 0;
        foreach ($umps as $ump) {
            $jatuhTempo = Carbon::parse($ump->jatuh_tempo)->startOfDay();
            $diffDays = (int) $today->diffInDays($jatuhTempo, false);

            $statusTenggat = null;
            $pesan = null;

            if ($diffDays < 0) {
                $statusTenggat = 'lewat';
                $hariLewat = abs($diffDays);
                $pesan = "Laporan UMP No. PPD {$ump->pencairan?->no_ppd} telah melewati jatuh tempo ({$ump->jatuh_tempo}) sebanyak {$hariLewat} hari.";
            } elseif ($diffDays <= 3) {
                $statusTenggat = 'mendekati';
                $pesan = "Laporan UMP No. PPD {$ump->pencairan?->no_ppd} mendekati batas waktu jatuh tempo ({$ump->jatuh_tempo}). Sisa {$diffDays} hari.";
            }

            if ($statusTenggat !== null) {
                $dedupKey = "ump_tenggat_{$ump->id}_{$statusTenggat}";
                $payload = [
                    'no_ppd' => $ump->pencairan?->no_ppd,
                    'ump_id' => $ump->id,
                    'url' => route('ump.detail', $ump->id),
                    'status_tenggat' => $statusTenggat,
                    'jatuh_tempo' => $ump->jatuh_tempo,
                    'pesan' => $pesan,
                ];

                $recipients = collect();
                if ($ump->pj) {
                    $recipients->push($ump->pj);
                }
                $pengaju = $ump->pencairan?->permohonan?->user;
                if ($pengaju && (! $ump->pj || $pengaju->id !== $ump->pj->id)) {
                    $recipients->push($pengaju);
                }

                foreach ($recipients as $recipient) {
                    NotificationService::kirim(
                        $recipient,
                        'ump_tenggat',
                        $payload,
                        $dedupKey
                    );
                    $count++;
                }
            }
        }

        $this->info("Pengingat UMP selesai diproses. Notifikasi terkirim/terverifikasi: {$count}.");

        return Command::SUCCESS;
    }
}
