<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tetapkan urutan hierarkis untuk Divisi Induk dan Unit Anak yang sudah ada
        $unitOrders = [
            'DIV-SEK' => 1,
            'UNT-SEK-DKL' => 1,
            'UNT-SEK-HUM' => 2,
            'UNT-SEK-KLM' => 3,

            'DIV-KEU' => 2,
            'UNT-KEU-KLR' => 1,
            'UNT-KEU-AKN' => 2,
            'UNT-KEU-TRM' => 3,

            'DIV-FND' => 3,
            'UNT-FND-INT' => 1,
            'UNT-FND-EKS' => 2,
            'UNT-FND-KPW-JTG' => 3,
            'UNT-FND-KPW-JTM' => 4,
            'UNT-FND-KPW-SSL' => 5,
            'UNT-FND-KPW-SMT' => 6,
            'UNT-FND-KPW-YOG' => 7,

            'DIV-PRG' => 4,
            'UNT-PRG-INT' => 1,

            'DIV-WKF' => 5,
            'UNT-WKF-INT' => 1,
        ];

        foreach ($unitOrders as $kode => $urutan) {
            DB::table('units')->where('kode', $kode)->update(['urutan' => $urutan]);
        }

        // Hanya jalankan sinkronisasi data personel jika database sudah ter-seed
        $untSekHumId = DB::table('units')->where('kode', 'UNT-SEK-HUM')->value('id');
        if (! $untSekHumId || ! DB::table('users')->exists()) {
            return;
        }

        // 2. Tambahkan akun baru Gibran Deniz Heryadi jika belum ada
        $hasGibran = DB::table('users')
            ->where('username', 'gibran.heryadi')
            ->orWhere('email', 'gibran.heryadi@alazharpeduli.or.id')
            ->exists();

        if (! $hasGibran) {
            $now = now();
            DB::table('users')->insert([
                'name' => 'Gibran Deniz Heryadi',
                'username' => 'gibran.heryadi',
                'email' => 'gibran.heryadi@alazharpeduli.or.id',
                'password' => Hash::make('password'),
                'role' => 'unit',
                'unit_id' => $untSekHumId,
                'leveling_id' => null,
                'jabatan' => 'Relawan IT Developer',
                'status_kepegawaian' => 'Relawan',
                'is_petugas_input' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Perbarui jabatan personel Sekretariat agar seragam dan akurat
        DB::table('users')->where('username', 'lias')->update([
            'jabatan' => 'Koordinator Diklat Litbang',
        ]);
        DB::table('users')->where('username', 'sigit.nugroho')->update([
            'jabatan' => 'Manager Humas, GA, dan IT',
        ]);
        DB::table('users')->where('username', 'bayu.setiawan')->update([
            'jabatan' => 'Koordinator Humas',
        ]);

        // 4. Pastikan Direktur Wakaf berada di DIV-WKF (konsisten dengan seluruh Kepala Divisi lain)
        $divWkfId = DB::table('units')->where('kode', 'DIV-WKF')->value('id');
        if ($divWkfId) {
            DB::table('users')->where('username', 'direktur.wakaf')->update([
                'unit_id' => $divWkfId,
            ]);
        }

        // 5. Sinkronisasi relasi atasan langsung (reporting line)
        $atasanCandidatesByUsername = [
            // Divisi Sekretariat — Diklat & Litbang
            'dedy.irwansyah' => ['rahmatullah.sidik'],
            'lias' => ['dedy.irwansyah'],
            'ahmad.zaki' => ['lias', 'dedy.irwansyah'],

            // Divisi Sekretariat — Humas, GA, dan IT (1 unit)
            'sigit.nugroho' => ['rahmatullah.sidik'],
            'rudiansah' => ['sigit.nugroho'],
            'stiyadi' => ['rudiansah'],
            'gibran.heryadi' => ['rudiansah'],
            'admin' => ['rudiansah'],

            'suryamin' => ['sigit.nugroho'],
            'hadi.cahyo' => ['suryamin'],
            'eko.mustakim' => ['suryamin'],
            'muh.sobran' => ['suryamin'],
            'ahmad.rizal' => ['suryamin'],
            'zaenal.mustofa' => ['suryamin'],

            'bayu.setiawan' => ['sigit.nugroho'],
            'nopen.setiawan' => ['bayu.setiawan'],
            'nadhilah.sifa' => ['bayu.setiawan'],
            'rosyadi' => ['bayu.setiawan'],
            'fauzi.suhada' => ['bayu.setiawan'],
            'fitri.wulandari' => ['bayu.setiawan'],
            'risdawati' => ['bayu.setiawan'],

            // Divisi Sekretariat — Kelembagaan
            'deden.salim' => ['rahmatullah.sidik'],
            'anas.priyogi' => ['deden.salim'],
            'jihan.sahra' => ['anas.priyogi'],
            'subakti' => ['anas.priyogi'],

            // Divisi Keuangan
            'rochadi.kohar' => ['direktur.keuangan'],
            'manager.keuangan.keluar' => ['direktur.keuangan'],
            'manager.keuangan.terima' => ['direktur.keuangan'],

            // Divisi Fundraising & Partnership (+ KPw)
            'manager.fundraising.int' => ['direktur.laz'],
            'manager.fundraising.eks' => ['direktur.laz'],
            'manager.kpw.jtg' => ['direktur.laz'],
            'kepala.kpw.jtg' => ['manager.kpw.jtg', 'direktur.laz'],
            'manager.kpw.jtm' => ['direktur.laz'],
            'kepala.kpw.jtm' => ['manager.kpw.jtm', 'direktur.laz'],
            'manager.kpw.ssl' => ['direktur.laz'],
            'kepala.kpw.ssl' => ['manager.kpw.ssl', 'direktur.laz'],
            'manager.kpw.smt' => ['direktur.laz'],
            'kepala.kpw.smt' => ['manager.kpw.smt', 'direktur.laz'],
            'manager.kpw.yog' => ['direktur.laz'],
            'kepala.kpw.yog' => ['manager.kpw.yog', 'direktur.laz'],

            // Divisi Program
            'manager.program' => ['kadiv.program'],

            // Divisi Wakaf
            'manager.wakaf' => ['direktur.wakaf'],
        ];

        $userIdsByUsername = DB::table('users')->pluck('id', 'username');

        foreach ($atasanCandidatesByUsername as $username => $candidateUsernames) {
            if (! isset($userIdsByUsername[$username])) {
                continue;
            }

            $atasanId = null;
            foreach ($candidateUsernames as $candidate) {
                if (isset($userIdsByUsername[$candidate])) {
                    $atasanId = (int) $userIdsByUsername[$candidate];
                    break;
                }
            }

            if ($atasanId !== null && $atasanId !== (int) $userIdsByUsername[$username]) {
                DB::table('users')
                    ->where('id', $userIdsByUsername[$username])
                    ->update(['atasan_id' => $atasanId]);
            }
        }
    }

    public function down(): void
    {
        // Data sync migration: perubahan urutan dan atasan tidak dibalikkan otomatis agar tidak menghapus penyesuaian admin.
    }
};
