<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan UMP - {{ $ump->pencairan?->no_ppd ?? 'UMP' . $ump->id }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 1.2cm 1.5cm 1.2cm 1.5cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #171c22;
            line-height: 1.3;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: bottom;
        }

        .logo-img {
            height: 52px;
            width: auto;
        }

        .office-address {
            font-size: 7.5pt;
            color: #485f7d;
            line-height: 1.2;
            margin-top: 4px;
        }

        .title-main {
            font-size: 13pt;
            font-weight: bold;
            color: #0f2f57;
            text-align: right;
            letter-spacing: 0.3px;
            margin: 0;
        }

        .title-sub {
            font-size: 8.5pt;
            font-weight: bold;
            color: #001b3e;
            text-align: right;
            background-color: #f1f5f9;
            display: inline-block;
            padding: 2px 8px;
            margin-top: 4px;
        }

        .header-rule {
            border: none;
            border-top: 2px solid #0f2f57;
            margin: 6px 0 10px 0;
        }

        .meta-table {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            margin-bottom: 10px;
        }

        .meta-table td {
            padding: 4px 8px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .meta-label {
            width: 140px;
            font-weight: bold;
            color: #334155;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #0f2f57;
            text-transform: uppercase;
            background-color: #e2e8f0;
            border: 1px solid #94a3b8;
            padding: 4px 8px;
            margin-top: 8px;
        }

        .value-box {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 10pt;
            font-weight: bold;
            color: #0f2f57;
            text-align: right;
            background-color: #ffffff;
        }

        .items-table {
            border: 1px solid #64748b;
            margin-top: 4px;
        }

        .items-table th {
            background-color: #0f2f57;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px;
            border: 1px solid #475569;
        }

        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 8.5pt;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .total-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 1.5px solid #64748b;
            padding: 6px;
        }

        .deficit-row td {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            border: 1px solid #f87171;
            padding: 6px;
        }

        .signature-group-title {
            font-size: 8pt;
            font-weight: bold;
            color: #334155;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 3px 8px;
            margin-top: 14px;
        }

        .signature-table {
            page-break-inside: avoid;
        }

        .sig-box {
            width: 50%;
            border: 1px solid #94a3b8;
            padding: 6px;
            vertical-align: top;
            background-color: #ffffff;
        }

        .sig-header {
            font-size: 8pt;
            font-weight: bold;
            color: #334155;
            text-align: center;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
        }

        .sig-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            text-align: center;
            text-decoration: underline;
            margin-top: 34px;
        }

        .sig-role {
            font-size: 7.5pt;
            color: #64748b;
            text-align: center;
            margin-top: 2px;
        }

        .sig-date {
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            margin-top: 2px;
        }

        .footer-table {
            margin-top: 14px;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }

        .footer-text {
            font-size: 7.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    @php
        $approvalsByTahap = $ump->approvals->keyBy('tahap');
        $signerPj = $ump->approvals->firstWhere('tahap', 'ttd_pj');
        $signerKadiv = $approvalsByTahap->get('ttd_kadiv');
        $signerMgrKeu = $approvalsByTahap->get('ttd_manager_keuangan');
        $signerKepKeu = $approvalsByTahap->get('ttd_kepala_keuangan');
        $selisih = (int) $ump->jumlah_total - (int) $ump->total_penggunaan;
        $permohonan = $ump->pencairan?->permohonan;
    @endphp

    {{-- HEADER KOP SURAT --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if ($logoBase64)
                    <img src="data:image/png;base64,{{ $logoBase64 }}" class="logo-img" alt="Logo LAZ Al Azhar">
                @else
                    <div style="font-size: 14pt; font-weight: bold; color: #006C4C;">LAZ AL AZHAR</div>
                @endif
                <div class="office-address">
                    <strong>LAZ AL AZHAR HEAD OFFICE</strong><br>
                    Kompleks Masjid Agung Al Azhar, Jl. Sisingamangaraja, Kebayoran Baru, Jakarta Selatan 12110<br>
                    Telp: (021) 7221504 | Email: info@alazharpeduli.or.id | Website: www.alazharpeduli.or.id
                </div>
            </td>
            <td style="width: 45%;">
                <div class="title-main">LAPORAN PERTANGGUNGJAWABAN<br>UANG MUKA PROGRAM (UMP)</div>
                <span class="title-sub">No. PPD / No. Uang Muka: {{ $ump->pencairan?->no_ppd ?? '-' }}</span>
            </td>
        </tr>
    </table>

    <hr class="header-rule">

    {{-- METADATA --}}
    <table class="meta-table">
        <tr>
            <td style="width: 50%; border-right: 1px solid #cbd5e1;">
                <table>
                    <tr>
                        <td class="meta-label">NO. PPD / UANG MUKA</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td class="font-bold">{{ $ump->pencairan?->no_ppd ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">TANGGAL TERIMA DANA</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td class="font-bold">{{ $ump->tgl_terima?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">UNIT / DIVISI</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td>{{ strtoupper($permohonan?->unit?->nama ?? '-') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; padding-left: 8px;">
                <table>
                    <tr>
                        <td class="meta-label">TUJUAN PENGAJUAN</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td class="font-bold">{{ strtoupper($ump->tujuan) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">PENANGGUNG JAWAB</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td>{{ strtoupper($ump->pj?->name ?? '-') }} ({{ $ump->pj?->jabatan ?? '-' }})</td>
                    </tr>
                    <tr>
                        <td class="meta-label">STATUS DOKUMEN</td>
                        <td style="width: 10px; text-align: center;">:</td>
                        <td class="font-bold" style="color: #0f2f57;">{{ strtoupper(str_replace('_', ' ', $ump->status)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- BAGIAN A: JUMLAH DITERIMA --}}
    <div class="section-title">Bagian A: Jumlah Diterima</div>
    <table>
        <tr>
            <td class="value-box">Rp {{ number_format($ump->jumlah_total, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- BAGIAN B: PENGGUNAAN DANA --}}
    <div class="section-title">Bagian B: Penggunaan Dana</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">NO</th>
                <th style="width: 16%;">TANGGAL</th>
                <th style="width: 42%;">RINCIAN PENGELUARAN</th>
                <th style="width: 16%;">NO. BUKTI</th>
                <th style="width: 20%;">JUMLAH (RP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ump->items->sortBy('tgl') as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $item->tgl?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-left font-bold">{{ $item->rincian }}</td>
                    <td class="text-center">{{ $item->no_bukti ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="5">Tidak ada baris penggunaan dana.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right uppercase">TOTAL PENGGUNAAN DANA:</td>
                <td class="text-right">Rp {{ number_format($ump->total_penggunaan, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- BAGIAN C: SALDO / DEFISIT --}}
    <div class="section-title">Bagian C: Saldo / Defisit (Jumlah Diterima - Penggunaan Dana)</div>
    <table>
        @if ($selisih < 0)
            <tr class="deficit-row">
                <td style="width: 60%;">DEFISIT / KEKURANGAN DANA (Penggunaan melebihi Jumlah Diterima)</td>
                <td class="text-right" style="width: 40%;">- Rp {{ number_format(abs($selisih), 0, ',', '.') }}</td>
            </tr>
        @else
            <tr>
                <td style="width: 60%; border: 1px solid #cbd5e1; padding: 6px 8px; font-weight: bold; color: #0f2f57;">
                    SALDO (Jumlah Diterima - Penggunaan Dana)
                </td>
                <td class="value-box" style="width: 40%;">Rp {{ number_format($selisih, 0, ',', '.') }}</td>
            </tr>
        @endif
    </table>
    @if ($ump->kekurangan > 0)
        <p style="font-size: 7.5pt; color: #991b1b; margin-top: 3px;">
            Catatan kekurangan dana wajib tercatat pada dokumen persetujuan UMP (FR-UMP-6).
        </p>
    @endif

    {{-- BAGIAN D: PEMBULATAN NOMINAL --}}
    <div class="section-title">Bagian D: Pembulatan Nominal (Kelipatan Rp 1.000)</div>
    <table>
        <tr>
            <td class="value-box">Rp {{ number_format($ump->pembulatan, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- GRUP TANDA TANGAN 1: UNIT --}}
    <div class="signature-group-title">Grup Unit</div>
    <table class="signature-table">
        <tr>
            <td class="sig-box">
                <div class="sig-header">DIBUAT OLEH (PENANGGUNG JAWAB)</div>
                <div class="sig-name">
                    {{ $signerPj?->signer_name ?: ($ump->pj?->name ?? '................................................') }}
                </div>
                <div class="sig-role">
                    {{ $signerPj?->signer_jabatan ?: ($ump->pj?->jabatan ?? 'Penanggung Jawab / Pemohon') }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerPj?->signed_at ? \Carbon\Carbon::parse($signerPj->signed_at)->format('d/m/Y') : '......................' }}
                </div>
            </td>
            <td class="sig-box">
                <div class="sig-header">DIPERIKSA OLEH (KEPALA DIVISI / DIROPS)</div>
                <div class="sig-name">
                    {{ $signerKadiv?->signer_name ?: '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $signerKadiv?->signer_jabatan ?: 'Kepala Divisi / Direktur Operasional' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerKadiv?->signed_at ? \Carbon\Carbon::parse($signerKadiv->signed_at)->format('d/m/Y') : '......................' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- GRUP TANDA TANGAN 2: DIVISI KEUANGAN --}}
    <div class="signature-group-title">Grup Divisi Keuangan</div>
    <table class="signature-table">
        <tr>
            <td class="sig-box">
                <div class="sig-header">PEMERIKSA (MANAJER ANGGARAN &amp; TREASURY)</div>
                <div class="sig-name">
                    {{ $signerMgrKeu?->signer_name ?: '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $signerMgrKeu?->signer_jabatan ?: 'Manajer Anggaran & Treasury' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerMgrKeu?->signed_at ? \Carbon\Carbon::parse($signerMgrKeu->signed_at)->format('d/m/Y') : '......................' }}
                </div>
            </td>
            <td class="sig-box">
                <div class="sig-header">DISETUJUI OLEH (KEPALA KEUANGAN)</div>
                <div class="sig-name">
                    {{ $signerKepKeu?->signer_name ?: '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $signerKepKeu?->signer_jabatan ?: 'Kepala Divisi Keuangan' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerKepKeu?->signed_at ? \Carbon\Carbon::parse($signerKepKeu->signed_at)->format('d/m/Y') : '......................' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- FOOTER COPYRIGHT --}}
    <table class="footer-table">
        <tr>
            <td style="width: 55%;" class="footer-text">
                Dicetak secara otomatis melalui Sistem SIGMA LAZ Al-Azhar pada {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB
            </td>
            <td style="width: 45%; text-align: right;" class="footer-text">
                Copyright @2024, www.alazharpeduli.or.id
            </td>
        </tr>
    </table>

</body>
</html>
