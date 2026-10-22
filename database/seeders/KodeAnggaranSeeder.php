<?php

namespace Database\Seeders;

use App\Models\AlokasiAnggaran;
use App\Models\KodeAnggaran;
use App\Models\TahunAnggaran;
use Illuminate\Database\Seeder;

class KodeAnggaranSeeder extends Seeder
{
    public function run(): void
    {
        $ta = TahunAnggaran::where('status', 'aktif')->first()
            ?? TahunAnggaran::where('tahun', 2026)->first();

        if (! $ta) {
            return;
        }

        // 1. Induk Utama Tingkat 1 (Kode '2')
        // Menghubungkan ke node '2' yang sudah ada di database jika user sudah membuatnya
        $node2 = KodeAnggaran::updateOrCreate(
            [
                'tahun_anggaran_id' => $ta->id,
                'kode' => '2',
            ],
            [
                'nama' => 'Penyaluran Dana ZIS & Operasional Amil',
                'parent_id' => null,
                'depth' => 1,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Penyaluran program ZISWAF dan hak kelola operasional amil',
                'is_active' => true,
            ]
        );

        // 2. Tingkat 2 (Kode 2.1, 2.2, 2.3, 2.4)
        KodeAnggaran::updateOrCreate(
            ['tahun_anggaran_id' => $ta->id, 'kode' => '2.1'],
            [
                'parent_id' => $node2->id,
                'nama' => 'Penyaluran Dana Zakat',
                'depth' => 2,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Alokasi program penyaluran 8 asnaf zakat',
                'is_active' => true,
            ]
        );

        KodeAnggaran::updateOrCreate(
            ['tahun_anggaran_id' => $ta->id, 'kode' => '2.2'],
            [
                'parent_id' => $node2->id,
                'nama' => 'Penyaluran Dana Infak Kemanusiaan',
                'depth' => 2,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Bantuan tanggap darurat dan bencana kemanusiaan',
                'is_active' => true,
            ]
        );

        KodeAnggaran::updateOrCreate(
            ['tahun_anggaran_id' => $ta->id, 'kode' => '2.3'],
            [
                'parent_id' => $node2->id,
                'nama' => 'Penyaluran Dana Infak Umum',
                'depth' => 2,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Penyaluran program infak sedekah umum',
                'is_active' => true,
            ]
        );

        $node24 = KodeAnggaran::updateOrCreate(
            ['tahun_anggaran_id' => $ta->id, 'kode' => '2.4'],
            [
                'parent_id' => $node2->id,
                'nama' => 'Penyaluran Dana Amil',
                'depth' => 2,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Hak kelola operasional dan kepegawaian amil LAZ',
                'is_active' => true,
            ]
        );

        // 3. Sub-Induk Tingkat 3 di bawah 2.4
        $node241 = KodeAnggaran::updateOrCreate(
            ['tahun_anggaran_id' => $ta->id, 'kode' => '2.4.1'],
            [
                'parent_id' => $node24->id,
                'nama' => 'Biaya Kepegawaian',
                'depth' => 3,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Gaji, tunjangan, dan pengembangan SDM amil',
                'is_active' => true,
            ]
        );

        $node242 = KodeAnggaran::updateOrCreate(
            ['tahun_anggaran_id' => $ta->id, 'kode' => '2.4.2'],
            [
                'parent_id' => $node24->id,
                'nama' => 'Biaya Utilitas',
                'depth' => 3,
                'budget_status' => 'disahkan',
                'deskripsi' => 'Biaya operasional kantor, IT, utilitas, dan sarana kerja',
                'is_active' => true,
            ]
        );

        // 4. Seluruh Pos Leaf Tingkat 4 di bawah 2.4.1 (Biaya Kepegawaian)
        $kepegawaianLeaves = [
            ['kode' => '2.4.1.1', 'nama' => 'Gaji Amil (Direksi, Sekretariat, LAZ Keuangan)', 'plafon' => 4560000000],
            ['kode' => '2.4.1.2', 'nama' => 'Biaya Perjalanan Dinas (Direksi, Sekretariat, LAZ Keuangan)', 'plafon' => 50200000],
            ['kode' => '2.4.1.3', 'nama' => 'Akomodasi Keluar Kota dan Kunjungan Kerja', 'plafon' => 50000000],
            ['kode' => '2.4.1.4', 'nama' => 'Biaya Lembur (Fundraising, Sekretariat dan Keuangan)', 'plafon' => 126000000],
            ['kode' => '2.4.1.5', 'nama' => 'Biaya Tunjangan Kesehatan', 'plafon' => 380000000],
            ['kode' => '2.4.1.6', 'nama' => 'Biaya BPJS Kesehatan dan BPJS Ketenagakerjaan', 'plafon' => 448853472],
            ['kode' => '2.4.1.7', 'nama' => 'Biaya THR', 'plafon' => 380000000],
            ['kode' => '2.4.1.8', 'nama' => 'Rapat Manajemen, Rapim, Raker & Evaluasi Lembaga', 'plafon' => 100000000],
            ['kode' => '2.4.1.9', 'nama' => 'Penghargaan Amil (Tarhib Ramadan)', 'plafon' => 50000000],
            ['kode' => '2.4.1.10', 'nama' => 'Amil Gathering dan Team Building', 'plafon' => 100000000],
            ['kode' => '2.4.1.11', 'nama' => 'Biaya Tambahan (Santunan Kematian, Hadiah Pernikahan, Pesangon)', 'plafon' => 40000000],
            ['kode' => '2.4.1.12', 'nama' => 'Biaya Penguatan Jaringan Lembaga (FOZ, CEO Forum)', 'plafon' => 40000000],
            ['kode' => '2.4.1.13', 'nama' => 'Biaya Jamuan Tamu dan Rapat', 'plafon' => 42000000],
            ['kode' => '2.4.1.14', 'nama' => 'Biaya Seragam Amil', 'plafon' => 41040000],
            ['kode' => '2.4.1.15', 'nama' => 'Temu Amil dan Silaturahmi Amil Nasional', 'plafon' => 59000000],
            ['kode' => '2.4.1.16', 'nama' => 'Biaya Insentif dan Transportasi DPS', 'plafon' => 76000000],
            ['kode' => '2.4.1.17', 'nama' => 'Milad Lembaga', 'plafon' => 50000000],
            ['kode' => '2.4.1.18', 'nama' => 'Pengembangan Kompetensi SDM & Sertifikasi Amil', 'plafon' => 221000000],
            ['kode' => '2.4.1.19', 'nama' => 'Legal dan ISO 9001-2015', 'plafon' => 100000000],
        ];

        foreach ($kepegawaianLeaves as $item) {
            $leafNode = KodeAnggaran::updateOrCreate(
                ['tahun_anggaran_id' => $ta->id, 'kode' => $item['kode']],
                [
                    'parent_id' => $node241->id,
                    'nama' => $item['nama'],
                    'depth' => 4,
                    'budget_status' => 'disahkan',
                    'is_active' => true,
                ]
            );

            AlokasiAnggaran::updateOrCreate(
                [
                    'kode_anggaran_id' => $leafNode->id,
                    'tahun_anggaran_id' => $ta->id,
                ],
                [
                    'plafon_awal' => $item['plafon'],
                    'plafon_revisi' => null,
                    'keterangan_revisi' => null,
                ]
            );
        }

        // 5. Seluruh Pos Leaf Tingkat 4 di bawah 2.4.2 (Biaya Utilitas)
        $utilitasLeaves = [
            ['kode' => '2.4.2.1', 'nama' => 'Biaya Listrik dan Telepon (Kantor Cirendeu)', 'plafon' => 72000000],
            ['kode' => '2.4.2.2', 'nama' => 'Biaya Internet (Kantor Cirendeu)', 'plafon' => 66000000],
            ['kode' => '2.4.2.3', 'nama' => 'Biaya Akomodasi Direksi', 'plafon' => 60000000],
            ['kode' => '2.4.2.4', 'nama' => 'Biaya Perlengkapan Kantor (ATK)', 'plafon' => 25000000],
            ['kode' => '2.4.2.5', 'nama' => 'Biaya Cetak (Kwitansi OP & PPD)', 'plafon' => 10000000],
            ['kode' => '2.4.2.6', 'nama' => 'Biaya Transportasi (Operasional Sekretariat)', 'plafon' => 54000000],
            ['kode' => '2.4.2.7', 'nama' => 'Biaya Keperluan Dapur (Rumah Tangga)', 'plafon' => 42000000],
            ['kode' => '2.4.2.8', 'nama' => 'Pengembangan Kantor Cirendeu', 'plafon' => 71261681],
            ['kode' => '2.4.2.9', 'nama' => 'Biaya Perawatan & Pemeliharaan Aset', 'plafon' => 80000000],
            ['kode' => '2.4.2.10', 'nama' => 'Biaya Pengiriman dan Pajak', 'plafon' => 20000000],
            ['kode' => '2.4.2.11', 'nama' => 'Pembelian Furniture Kantor', 'plafon' => 90000000],
            ['kode' => '2.4.2.12', 'nama' => 'Peralatan Sarana Kerja (PC, Laptop, Printer)', 'plafon' => 217600000],
            ['kode' => '2.4.2.13', 'nama' => 'Pengembangan IT dan Sistem', 'plafon' => 200000000],
            ['kode' => '2.4.2.18', 'nama' => 'Inovasi dan Pengembangan', 'plafon' => 50000000],
            ['kode' => '2.4.2.19', 'nama' => 'Biaya Audit KAP dan Visitasi', 'plafon' => 65000000],
            ['kode' => '2.4.2.20', 'nama' => 'Biaya Aplikasi Jurnal Akuntansi & Penyusunan Lapkeu', 'plafon' => 70000000],
        ];

        foreach ($utilitasLeaves as $item) {
            $leafNode = KodeAnggaran::updateOrCreate(
                ['tahun_anggaran_id' => $ta->id, 'kode' => $item['kode']],
                [
                    'parent_id' => $node242->id,
                    'nama' => $item['nama'],
                    'depth' => 4,
                    'budget_status' => 'disahkan',
                    'is_active' => true,
                ]
            );

            AlokasiAnggaran::updateOrCreate(
                [
                    'kode_anggaran_id' => $leafNode->id,
                    'tahun_anggaran_id' => $ta->id,
                ],
                [
                    'plafon_awal' => $item['plafon'],
                    'plafon_revisi' => null,
                    'keterangan_revisi' => null,
                ]
            );
        }
    }
}
