<?php

namespace Database\Seeders;

use App\Models\Asnaf;
use App\Models\Leveling;
use App\Models\Penomoran;
use App\Models\Permohonan;
use App\Models\ProgramUtama;
use App\Models\Setting;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tahun Anggaran
        TahunAnggaran::updateOrCreate(
            ['tahun' => 2026],
            ['status' => 'aktif']
        );

        // 2. Sumber Dana
        $sumberDanaZakat = SumberDana::updateOrCreate(
            ['nama' => 'Zakat'],
            ['butuh_asnaf' => true, 'is_active' => true]
        );

        $sumberDanaInfakUmum = SumberDana::updateOrCreate(
            ['nama' => 'Infak Umum'],
            ['butuh_asnaf' => false, 'is_active' => true]
        );

        SumberDana::updateOrCreate(
            ['nama' => 'Infak Khusus / Kemanusiaan'],
            ['butuh_asnaf' => false, 'is_active' => true]
        );

        // 3. Divisi Induk (parent_id null, tipe = 'divisi') & Unit Anak (parent_id -> Divisi, tipe = 'unit')

        // 1. DIV-SEK: Divisi Sekretariat (Zakat / Amil)
        $divSek = Unit::updateOrCreate(
            ['kode' => 'DIV-SEK'],
            [
                'nama' => 'Divisi Sekretariat',
                'tipe' => 'divisi',
                'parent_id' => null,
                'sumber_dana_default_id' => $sumberDanaZakat->id,
                'is_active' => true,
            ]
        );
        $untSekDir = Unit::updateOrCreate(
            ['kode' => 'UNT-SEK-DIR'],
            ['nama' => 'Unit Direksi', 'tipe' => 'unit', 'parent_id' => $divSek->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );
        $untSekKlm = Unit::updateOrCreate(
            ['kode' => 'UNT-SEK-KLM'],
            ['nama' => 'Unit Kelembagaan', 'tipe' => 'unit', 'parent_id' => $divSek->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );
        $untSekHum = Unit::updateOrCreate(
            ['kode' => 'UNT-SEK-HUM'],
            ['nama' => 'Unit Humas, GA, dan IT', 'tipe' => 'unit', 'parent_id' => $divSek->id, 'sumber_dana_default_id' => $sumberDanaInfakUmum->id, 'is_active' => true]
        );
        $untSekDkl = Unit::updateOrCreate(
            ['kode' => 'UNT-SEK-DKL'],
            ['nama' => 'Unit Diklat & Litbang', 'tipe' => 'unit', 'parent_id' => $divSek->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );

        // 2. DIV-KEU: Divisi Keuangan (Zakat / Amil)
        $divKeu = Unit::updateOrCreate(
            ['kode' => 'DIV-KEU'],
            [
                'nama' => 'Divisi Keuangan',
                'tipe' => 'divisi',
                'parent_id' => null,
                'sumber_dana_default_id' => $sumberDanaZakat->id,
                'is_active' => true,
            ]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-KEU-DIR'],
            ['nama' => 'Unit Direksi', 'tipe' => 'unit', 'parent_id' => $divKeu->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-KEU-KLR'],
            ['nama' => 'Unit Pengeluaran', 'tipe' => 'unit', 'parent_id' => $divKeu->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-KEU-AKN'],
            ['nama' => 'Unit Anggaran & Akuntansi', 'tipe' => 'unit', 'parent_id' => $divKeu->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-KEU-TRM'],
            ['nama' => 'Unit Penerimaan', 'tipe' => 'unit', 'parent_id' => $divKeu->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );

        // 3. DIV-FND: Divisi Fundraising & Partnership (Infak Umum)
        $divFnd = Unit::updateOrCreate(
            ['kode' => 'DIV-FND'],
            [
                'nama' => 'Divisi Fundraising & Partnership',
                'tipe' => 'divisi',
                'parent_id' => null,
                'sumber_dana_default_id' => $sumberDanaInfakUmum->id,
                'is_active' => true,
            ]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-FND-DIR'],
            ['nama' => 'Unit Direksi', 'tipe' => 'unit', 'parent_id' => $divFnd->id, 'sumber_dana_default_id' => $sumberDanaInfakUmum->id, 'is_active' => true]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-FND-INT'],
            ['nama' => 'Unit Internal Fundraising', 'tipe' => 'unit', 'parent_id' => $divFnd->id, 'sumber_dana_default_id' => $sumberDanaInfakUmum->id, 'is_active' => true]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-FND-EKS'],
            ['nama' => 'Unit Eksternal Fundraising', 'tipe' => 'unit', 'parent_id' => $divFnd->id, 'sumber_dana_default_id' => $sumberDanaInfakUmum->id, 'is_active' => true]
        );

        // Unit-unit Kantor Perwakilan Wilayah (KPw) di bawah Divisi Fundraising & Partnership
        $kpwUnits = [
            ['kode' => 'UNT-FND-KPW-JTG', 'nama' => 'Unit KPw Jawa Tengah'],
            ['kode' => 'UNT-FND-KPW-JTM', 'nama' => 'Unit KPw Jawa Timur'],
            ['kode' => 'UNT-FND-KPW-SSL', 'nama' => 'Unit KPw Sulawesi Selatan'],
            ['kode' => 'UNT-FND-KPW-SMT', 'nama' => 'Unit KPw Sumatera Barat'],
            ['kode' => 'UNT-FND-KPW-YOG', 'nama' => 'Unit KPw Yogyakarta'],
        ];

        foreach ($kpwUnits as $kpw) {
            Unit::updateOrCreate(
                ['kode' => $kpw['kode']],
                [
                    'nama' => $kpw['nama'],
                    'tipe' => 'unit',
                    'parent_id' => $divFnd->id,
                    'sumber_dana_default_id' => $sumberDanaZakat->id,
                    'is_active' => true,
                ]
            );
        }

        // 4. DIV-PRG: Divisi Program (Zakat)
        $divPrg = Unit::updateOrCreate(
            ['kode' => 'DIV-PRG'],
            [
                'nama' => 'Divisi Program',
                'tipe' => 'divisi',
                'parent_id' => null,
                'sumber_dana_default_id' => $sumberDanaZakat->id,
                'is_active' => true,
            ]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-PRG-INT'],
            ['nama' => 'Unit Program', 'tipe' => 'unit', 'parent_id' => $divPrg->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );

        // 5. DIV-WKF: Divisi Wakaf (Zakat)
        $divWkf = Unit::updateOrCreate(
            ['kode' => 'DIV-WKF'],
            [
                'nama' => 'Divisi Wakaf',
                'tipe' => 'divisi',
                'parent_id' => null,
                'sumber_dana_default_id' => $sumberDanaZakat->id,
                'is_active' => true,
            ]
        );
        Unit::updateOrCreate(
            ['kode' => 'UNT-WKF-INT'],
            ['nama' => 'Unit Wakaf', 'tipe' => 'unit', 'parent_id' => $divWkf->id, 'sumber_dana_default_id' => $sumberDanaZakat->id, 'is_active' => true]
        );

        // Pembersihan divisi legacy DIV-KPW-*
        Unit::where('kode', 'like', 'DIV-KPW%')->delete();

        // Pembersihan & migrasi kode unit legacy jika ada
        $legacyMappings = [
            'UNT-TU' => 'UNT-SEK-DKL',
            'UNT-IT' => 'UNT-SEK-HUM',
            'UNT-GA' => 'UNT-SEK-HUM',
            'UNT-KOM' => 'UNT-SEK-HUM',
            'UNT-KLM' => 'UNT-SEK-KLM',
            'UNT-DKL' => 'UNT-SEK-DKL',
            'DIV-HUM' => 'DIV-SEK',
            'DIV-KLM' => 'DIV-SEK',
            'DIV-DKL' => 'DIV-SEK',
            'SEK' => 'DIV-SEK',
            'DIR' => 'UNT-SEK-DIR',
            'KLM' => 'UNT-SEK-KLM',
            'HUM' => 'UNT-SEK-HUM',
            'DKL' => 'UNT-SEK-DKL',
        ];

        foreach ($legacyMappings as $oldKode => $newKode) {
            $oldU = Unit::where('kode', $oldKode)->first();
            $newU = Unit::where('kode', $newKode)->first();
            if ($oldU && $newU && $oldU->id !== $newU->id) {
                User::where('unit_id', $oldU->id)->update(['unit_id' => $newU->id]);
                Permohonan::where('unit_id', $oldU->id)->update(['unit_id' => $newU->id]);
                Penomoran::where('unit_id', $oldU->id)->update(['unit_id' => $newU->id]);
                Unit::where('parent_id', $oldU->id)->update(['parent_id' => $newU->id]);
                $oldU->delete();
            }
        }
        // 4. Asnaf
        $asnafList = [
            'Fakir',
            'Miskin',
            'Amil',
            'Muallaf',
            'Riqab',
            'Gharimin',
            'Fisabilillah',
            'Ibnu Sabil',
        ];

        foreach ($asnafList as $namaAsnaf) {
            Asnaf::updateOrCreate(
                ['nama' => $namaAsnaf],
                ['is_active' => true]
            );
        }

        // 5. Program Utama
        $programUtamaList = [
            'Program Dakwah',
            'Program Pendidikan',
            'Program Sosial & Kemanusiaan',
            'Program Pemberdayaan Ekonomi',
            'Operasional Kesekretariatan',
        ];

        foreach ($programUtamaList as $namaProgram) {
            ProgramUtama::updateOrCreate(
                ['nama' => $namaProgram],
                ['is_active' => true]
            );
        }

        // 6. Levelings
        $levelings = [
            [
                'nama' => 'Supervisor',
                'batas_bawah' => 0,
                'batas_atas' => 2000000,
                'urutan' => 1,
            ],
            [
                'nama' => 'Manager',
                'batas_bawah' => 2000001,
                'batas_atas' => 10000000,
                'urutan' => 2,
            ],
            [
                'nama' => 'Kepala Divisi / Direksi',
                'batas_bawah' => 10000001,
                'batas_atas' => null,
                'urutan' => 3,
            ],
        ];

        Leveling::where('urutan', '>', 3)->delete();

        foreach ($levelings as $leveling) {
            Leveling::updateOrCreate(
                ['urutan' => $leveling['urutan']],
                $leveling
            );
        }

        // 7. Settings
        $settings = [
            'ambang.limit' => 80,
            'ambang.over' => 100,
            'pembulatan.kelipatan' => 1000,
            'pembulatan.arah' => 'terdekat',
            'ump.tenggat_hari' => 14,
            'keuangan.mode' => 'atas_nama',
            'kode.max_depth' => 4,
        ];

        foreach ($settings as $key => $val) {
            Setting::set($key, $val);
        }
    }
}
