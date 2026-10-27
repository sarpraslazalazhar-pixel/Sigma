<?php

namespace App\Services;

use App\Mail\ReminderNotificationMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    public static function kirim(User $penerima, string $type, array $data, string $dedupKey): void
    {
        try {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => $type,
                'notifiable_type' => $penerima->getMorphClass(),
                'notifiable_id' => $penerima->id,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'dedup_key' => $dedupKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Idempoten: notifikasi dengan dedup_key ini sudah pernah terkirim ke user
            return;
        }

        static::kirimEmailJikaAktif($penerima, $type, $data);
    }

    protected static function kirimEmailJikaAktif(User $penerima, string $type, array $data): void
    {
        $emailSetting = Setting::get('notifikasi.email_aktif', 'nonaktif');
        $isAktif = $emailSetting === 'aktif' || $emailSetting === true || $emailSetting === 1 || $emailSetting === '1';

        if (! $isAktif) {
            return;
        }

        if (empty($penerima->email) || ! filter_var($penerima->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        rescue(function () use ($penerima, $type, $data) {
            Mail::to($penerima->email)->send(new ReminderNotificationMail($penerima, $type, $data));
        }, function (\Throwable $e) use ($penerima) {
            Log::warning("Gagal mengirim email notifikasi ke {$penerima->email}: {$e->getMessage()}");
        }, report: false);
    }
}
