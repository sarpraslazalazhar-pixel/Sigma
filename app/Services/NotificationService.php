<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
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
        }
    }
}
