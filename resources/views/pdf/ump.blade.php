<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan UMP - {{ $ump->pencairan?->no_ppd ?? 'UMP' . $ump->id }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 1cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        .outer-border {
            border: 1px solid #000000;
            padding: 10px 12px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-img {
            height: 48px;
            width: auto;
        }

        .meta-table {
            width: auto;
            margin-top: 4px;
        }

        .meta-table td {
            padding: 1.5px 0;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .main-table {
            border: 1px solid #000000;
            margin-top: 8px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000000;
            padding: 3.5px 5px;
            font-size: 8pt;
        }

        .bg-shade {
            background-color: #f2f2f2;
        }

        .sig-table {
            border: 1px solid #000000;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .sig-table th,
        .sig-table td {
            border: 1px solid #000000;
            padding: 3px 4px;
            font-size: 8pt;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="outer-border">

    @php
        $approvalsByTahap = $ump->approvals->keyBy('tahap');
        $signerPj = $ump->approvals->firstWhere('tahap', 'ttd_pj');
        $signerKadiv = $approvalsByTahap->get('ttd_kadiv');
        $signerMgrKeu = $approvalsByTahap->get('ttd_manager_keuangan');
        $signerKepKeu = $approvalsByTahap->get('ttd_kepala_keuangan');
        $selisih = (int) $ump->jumlah_total - (int) $ump->total_penggunaan;
        $permohonan = $ump->pencairan?->permohonan;

        $pjUser = $ump->pj ?: $permohonan?->user;
        $signerPjName = $signerPj?->signer_name ?: ($pjUser?->name ?? 'Deden Nurdin Salim');
        $signerPjJabatan = $signerPj?->signer_jabatan ?: ($pjUser?->jabatan ?? 'Manager HRD');

        $kadivUser = $permohonan?->unit?->parent?->users()->whereHas('leveling', fn($q) => $q->where('urutan', 3))->first()
            ?? \App\Models\User::where('username', 'rahmatullah.sidik')->first();
        $signerKadivName = $signerKadiv?->signer_name ?: ($kadivUser?->name ?? 'Rahmatullah Sidik');
        $signerKadivJabatan = $signerKadiv?->signer_jabatan ?: 'Kepala Sekretariat';

        $mgrKeuUser = \App\Models\User::where('username', 'rochadi.kohar')->first();
        $signerMgrKeuName = $signerMgrKeu?->signer_name ?: ($mgrKeuUser?->name ?? 'Rochadi Kohar');
        $signerMgrKeuJabatan = $signerMgrKeu?->signer_jabatan ?: 'Manajer Anggaran & Treasury';

        $kepKeuUser = \App\Models\User::where('username', 'sardi.haryono')->first();
        $signerKepKeuName = $signerKepKeu?->signer_name ?: ($kepKeuUser?->name ?? 'Sardi Haryono');
        $signerKepKeuJabatan = $signerKepKeu?->signer_jabatan ?: 'Kepala Keuangan';

        $items = $ump->items->sortBy('tgl')->values();
        $maxRows = max(9, $items->count());
        $noPpdDisplay = $ump->pencairan?->no_ppd ? (str_starts_with($ump->pencairan->no_ppd, 'PPD') ? $ump->pencairan->no_ppd : 'PPD. ' . $ump->pencairan->no_ppd) : 'PPD.';
    @endphp

    {{-- HEADER KOP LAPORAN --}}
    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <div style="font-size: 9.5pt; font-weight: bold; margin-bottom: 6px;">
                    Laporan {{ $ump->tujuan }}
                </div>
                <table class="meta-table">
                    <tr>
                        <td style="width: 175px;">No. PPD / No. Uang Muka</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td>{{ $noPpdDisplay }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Penerimaan Dana</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $ump->tgl_terima ? \Carbon\Carbon::parse($ump->tgl_terima)->locale('id')->translatedFormat('j F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td>Tujuan Pengajuan</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $ump->tujuan }}</td>
                    </tr>
                    <tr>
                        <td>Penanggung Jawab</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $signerPjName }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; text-align: right; vertical-align: top;">
                @if ($logoBase64)
                    <img src="data:image/png;base64,{{ $logoBase64 }}" class="logo-img" alt="Logo LAZ Al Azhar">
                @else
                    <div style="font-size: 13pt; font-weight: bold; color: #001b44;">AL AZHAR<br><span style="font-size: 8pt; font-weight: normal;">Lembaga Amil Zakat</span></div>
                @endif
            </td>
        </tr>
    </table>

    {{-- TABEL UTAMA PENGGUNAAN DANA --}}
    <table class="main-table">
        <tbody>
            {{-- BARIS A --}}
            <tr>
                <td style="width: 35px; text-align: center; font-weight: bold;" class="bg-shade">A.</td>
                <td colspan="3" style="font-weight: bold;" class="bg-shade">JUMLAH DITERIMA</td>
                <td style="width: 110px; text-align: right; font-weight: bold;">{{ number_format($ump->jumlah_total, 0, ',', '.') }}</td>
            </tr>

            {{-- BARIS B --}}
            <tr>
                <td style="text-align: center; font-weight: bold;" class="bg-shade">B.</td>
                <td colspan="3" style="font-weight: bold;" class="bg-shade">PENGGUNAAN DANA</td>
                <td class="bg-shade"></td>
            </tr>

            {{-- HEADER TABEL BARIS --}}
            <tr class="bg-shade" style="font-weight: bold; text-align: center;">
                <td style="text-align: center;">NO:</td>
                <td style="width: 105px; text-align: center;">TGL</td>
                <td style="text-align: center;">RINCIAN PENGELUARAN</td>
                <td style="width: 85px; text-align: center;">NO BUKTI</td>
                <td style="width: 110px; text-align: center;">JUMLAH</td>
            </tr>

            {{-- DATA RINCIAN DENGAN PADDING BARIS --}}
            @for ($i = 0; $i < $maxRows; $i++)
                @php $item = $items->get($i); @endphp
                <tr>
                    <td style="text-align: center; height: 18px;">{{ $i + 1 }}</td>
                    <td style="text-align: center;">
                        {{ $item && $item->tgl ? \Carbon\Carbon::parse($item->tgl)->locale('id')->translatedFormat('j F Y') : '' }}
                    </td>
                    <td style="text-align: left; padding-left: 6px;">
                        {{ $item ? $item->rincian : '' }}
                    </td>
                    <td style="text-align: center;">
                        {{ $item ? ($item->no_bukti ?: '-') : '' }}
                    </td>
                    <td style="text-align: right; padding-right: 6px;">
                        {{ $item ? number_format($item->jumlah, 0, ',', '.') : '' }}
                    </td>
                </tr>
            @endfor

            {{-- TOTAL PENGGUNAAN DANA --}}
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold; padding-right: 12px;">TOTAL PENGGUNAAN DANA</td>
                <td style="text-align: right; font-weight: bold; padding-right: 6px;">{{ number_format($ump->total_penggunaan, 0, ',', '.') . '.00' }}</td>
            </tr>

            {{-- BARIS C --}}
            <tr>
                <td style="text-align: center; font-weight: bold;" class="bg-shade">C.</td>
                <td colspan="2" style="font-weight: bold;" class="bg-shade">SALDO/DEFISIT (A - B)</td>
                <td style="text-align: left; font-weight: bold; padding-left: 6px;">Rp</td>
                <td style="text-align: right; padding-right: 6px;">{{ $selisih == 0 ? '-' : number_format($selisih, 0, ',', '.') }}</td>
            </tr>

            {{-- BARIS D --}}
            <tr>
                <td style="text-align: center; font-weight: bold;" class="bg-shade">D.</td>
                <td colspan="2" class="bg-shade">Pembulatan Nominal</td>
                <td style="text-align: left; font-weight: bold; padding-left: 6px;">Rp</td>
                <td style="text-align: right; padding-right: 6px;">{{ $ump->pembulatan == 0 ? '-' : number_format($ump->pembulatan, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- KOTAK TANDA TANGAN (SESUAI FISIK DOKUMEN ORGANISASI) --}}
    <table class="sig-table">
        <thead>
            <tr>
                <td rowspan="2" style="width: 25%; text-align: center; vertical-align: top; font-weight: bold;">Dibuat oleh</td>
                <td rowspan="2" style="width: 25%; text-align: center; vertical-align: top; font-weight: bold;">Diperiksa Oleh</td>
                <td colspan="2" style="width: 50%; text-align: center; font-weight: bold;">Divisi Keuangan</td>
            </tr>
            <tr>
                <td style="width: 25%; text-align: center; font-weight: bold;">Pemeriksa</td>
                <td style="width: 25%; text-align: center; font-weight: bold;">Disetujui Oleh</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="height: 60px; vertical-align: bottom; text-align: center; padding-bottom: 4px;">
                    @if ($signerPj)
                        <div style="font-size: 7pt; color: #166534; font-weight: bold;">✓ Disetujui Digital</div>
                        <div style="font-size: 6.5pt; color: #666;">{{ \Carbon\Carbon::parse($signerPj->signed_at)->format('d/m/Y') }}</div>
                    @endif
                </td>
                <td style="height: 60px; vertical-align: bottom; text-align: center; padding-bottom: 4px;">
                    @if ($signerKadiv)
                        <div style="font-size: 7pt; color: #166534; font-weight: bold;">✓ Disetujui Digital</div>
                        <div style="font-size: 6.5pt; color: #666;">{{ \Carbon\Carbon::parse($signerKadiv->signed_at)->format('d/m/Y') }}</div>
                    @endif
                </td>
                <td style="height: 60px; vertical-align: bottom; text-align: center; padding-bottom: 4px;">
                    @if ($signerMgrKeu)
                        <div style="font-size: 7pt; color: #166534; font-weight: bold;">✓ Disetujui Digital</div>
                        <div style="font-size: 6.5pt; color: #666;">{{ \Carbon\Carbon::parse($signerMgrKeu->signed_at)->format('d/m/Y') }}</div>
                    @endif
                </td>
                <td style="height: 60px; vertical-align: bottom; text-align: center; padding-bottom: 4px;">
                    @if ($signerKepKeu)
                        <div style="font-size: 7pt; color: #166534; font-weight: bold;">✓ Disetujui Digital</div>
                        <div style="font-size: 6.5pt; color: #666;">{{ \Carbon\Carbon::parse($signerKepKeu->signed_at)->format('d/m/Y') }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 2px;">
                    <div style="font-weight: bold;">{{ $signerPjName }}</div>
                    <div style="font-size: 7.5pt; color: #333;">{{ $signerPjJabatan }}</div>
                </td>
                <td style="padding: 4px 2px;">
                    <div style="font-weight: bold;">{{ $signerKadivName }}</div>
                    <div style="font-size: 7.5pt; color: #333;">{{ $signerKadivJabatan }}</div>
                </td>
                <td style="padding: 4px 2px;">
                    <div style="font-weight: bold;">{{ $signerMgrKeuName }}</div>
                    <div style="font-size: 7.5pt; color: #333;">{{ $signerMgrKeuJabatan }}</div>
                </td>
                <td style="padding: 4px 2px;">
                    <div style="font-weight: bold;">{{ $signerKepKeuName }}</div>
                    <div style="font-size: 7.5pt; color: #333;">{{ $signerKepKeuJabatan }}</div>
                </td>
            </tr>
        </tbody>
    </table>

</div>

</body>
</html>
