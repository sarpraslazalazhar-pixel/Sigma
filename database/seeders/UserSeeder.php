<?php

namespace Database\Seeders;

use App\Models\Leveling;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Unit-unit spesifik sesuai penempatan
        $untSekDir = Unit::where('kode', 'UNT-SEK-DIR')->first();
        $untSekKlm = Unit::where('kode', 'UNT-SEK-KLM')->first();
        $untSekHum = Unit::where('kode', 'UNT-SEK-HUM')->first();
        $untSekDkl = Unit::where('kode', 'UNT-SEK-DKL')->first();

        $untKeuDir = Unit::where('kode', 'UNT-KEU-DIR')->first();
        $untKeuKlr = Unit::where('kode', 'UNT-KEU-KLR')->first();
        $untKeuAkn = Unit::where('kode', 'UNT-KEU-AKN')->first();

        $untFndDir = Unit::where('kode', 'UNT-FND-DIR')->first();
        $untFndInt = Unit::where('kode', 'UNT-FND-INT')->first();
        $untFndEks = Unit::where('kode', 'UNT-FND-EKS')->first();

        $untPrgInt = Unit::where('kode', 'UNT-PRG-INT')->first();
        $untWkfInt = Unit::where('kode', 'UNT-WKF-INT')->first();

        $untKpwJtg = Unit::where('kode', 'UNT-FND-KPW-JTG')->first();
        $untKpwJtm = Unit::where('kode', 'UNT-FND-KPW-JTM')->first();
        $untKpwSsl = Unit::where('kode', 'UNT-FND-KPW-SSL')->first();
        $untKpwSmt = Unit::where('kode', 'UNT-FND-KPW-SMT')->first();
        $untKpwYog = Unit::where('kode', 'UNT-FND-KPW-YOG')->first();
        $lvlSupervisor = Leveling::where('urutan', 1)->first();
        $lvlManager = Leveling::where('urutan', 2)->first();
        $lvlKadiv = Leveling::where('urutan', 3)->first();

        $defaultPassword = Hash::make('password');

        $users = [
            // 1. Divisi Sekretariat
            [
                'name' => 'Rahmatullah Sidik',
                'username' => 'rahmatullah.sidik',
                'email' => 'rahmatullah@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekDir?->id,
                'leveling_id' => $lvlKadiv?->id,
                'jabatan' => 'Kepala Divisi Sekretariat / Direktur Operasional',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Deden Nurdin Salim',
                'username' => 'deden.salim',
                'email' => 'deden@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekKlm?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Kelembagaan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Subakti',
                'username' => 'subakti',
                'email' => 'subakti@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekKlm?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Staf Kelembagaan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Rudiansah',
                'username' => 'rudiansah',
                'email' => 'rudiansah@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekKlm?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Staf Kelembagaan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manager Humas, IT & GA',
                'username' => 'manager.humas',
                'email' => 'manager.humas@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekHum?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Humas, IT, dan GA',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manager Diklat & Litbang',
                'username' => 'manager.diklat',
                'email' => 'manager.diklat@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekDkl?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Diklat Litbang',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Staf TU / Koordinator Sekretariat',
                'username' => 'tu.sekretariat',
                'email' => 'tu.sekretariat@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekDkl?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Koordinator Sekretariat',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Koordinator IT',
                'username' => 'koordinator.it',
                'email' => 'koordinator.it@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekHum?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Koordinator IT',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Koordinator General Affair',
                'username' => 'koordinator.ga',
                'email' => 'koordinator.ga@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekHum?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Koordinator General Affair',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Koordinator Komunikasi',
                'username' => 'koordinator.komunikasi',
                'email' => 'koordinator.komunikasi@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekHum?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Koordinator Komunikasi',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Koordinator Kelembagaan',
                'username' => 'koordinator.kelembagaan',
                'email' => 'koordinator.kelembagaan@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untSekKlm?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'Koordinator Kelembagaan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Administrator IT',
                'username' => 'admin',
                'email' => 'admin@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'admin',
                'unit_id' => $untSekHum?->id,
                'leveling_id' => $lvlSupervisor?->id,
                'jabatan' => 'IT System Administrator',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],

            // 2. Divisi Keuangan
            [
                'name' => 'Direktur Keuangan',
                'username' => 'direktur.keuangan',
                'email' => 'direktur.keuangan@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'keuangan',
                'unit_id' => $untKeuDir?->id,
                'leveling_id' => $lvlKadiv?->id,
                'jabatan' => 'Kepala Divisi Keuangan / Direktur Keuangan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Rochadi Kohar',
                'username' => 'rochadi.kohar',
                'email' => 'rochadi@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'keuangan',
                'unit_id' => $untKeuAkn?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Anggaran dan Akuntansi Keuangan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manager Pengeluaran Keuangan',
                'username' => 'manager.keuangan.keluar',
                'email' => 'manager.keluar@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'keuangan',
                'unit_id' => $untKeuKlr?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Pengeluaran Keuangan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],

            // 3. Divisi Fundraising & Partnership
            [
                'name' => 'Direktur LAZ Al Azhar',
                'username' => 'direktur.laz',
                'email' => 'direktur.laz@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untFndDir?->id,
                'leveling_id' => $lvlKadiv?->id,
                'jabatan' => 'Kepala Divisi LAZ Al Azhar / Direktur LAZ Al Azhar',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manager Fundraising Internal',
                'username' => 'manager.fundraising.int',
                'email' => 'fnd.internal@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untFndInt?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Fundraising Internal',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Manager Fundraising Eksternal',
                'username' => 'manager.fundraising.eks',
                'email' => 'fnd.eksternal@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untFndEks?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Fundraising Eksternal',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],

            // 4. Divisi Program
            [
                'name' => 'Manager Program LAZ Al Azhar',
                'username' => 'manager.program',
                'email' => 'manager.program@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untPrgInt?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Manager Program LAZ Al Azhar',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],

            // 5. Divisi Wakaf
            [
                'name' => 'Direktur Wakaf',
                'username' => 'direktur.wakaf',
                'email' => 'direktur.wakaf@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untWkfInt?->id,
                'leveling_id' => $lvlKadiv?->id,
                'jabatan' => 'Kepala Divisi Wakaf / Direktur Wakaf',
                'status_kepegawaian' => 'Tetap',
                'is_active' => true,
            ],

            // 6. Unit KPw di bawah Divisi Fundraising & Partnership
            [
                'name' => 'Kepala KPw Jawa Tengah',
                'username' => 'kepala.kpw.jtg',
                'email' => 'kpw.jtg@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untKpwJtg?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Kepala Kantor Perwakilan Jawa Tengah',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Kepala KPw Jawa Timur',
                'username' => 'kepala.kpw.jtm',
                'email' => 'kpw.jtm@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untKpwJtm?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Kepala Kantor Perwakilan Jawa Timur',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Kepala KPw Sulawesi Selatan',
                'username' => 'kepala.kpw.ssl',
                'email' => 'kpw.ssl@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untKpwSsl?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Kepala Kantor Perwakilan Sulawesi Selatan',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Kepala KPw Sumatera Barat',
                'username' => 'kepala.kpw.smt',
                'email' => 'kpw.smt@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untKpwSmt?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Kepala Kantor Perwakilan Sumatera Barat',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Kepala KPw Yogyakarta',
                'username' => 'kepala.kpw.yog',
                'email' => 'kpw.yog@alazharpeduli.or.id',
                'password' => $defaultPassword,
                'role' => 'unit',
                'unit_id' => $untKpwYog?->id,
                'leveling_id' => $lvlManager?->id,
                'jabatan' => 'Kepala Kantor Perwakilan Yogyakarta',
                'status_kepegawaian' => 'Tetap',
                'is_petugas_input' => false,
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['username' => $user['username']], $user);
        }
    }
}
