<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pencairan Permohonan Dana - {{ $pencairan->no_ppd }}</title>
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
            vertical-align: top;
        }

        .logo-img {
            height: 48px;
            width: auto;
        }

        .office-address {
            font-size: 7.5pt;
            color: #485f7d;
            line-height: 1.2;
            margin-top: 4px;
        }

        .title-box {
            text-align: right;
        }

        .title-main {
            font-size: 14pt;
            font-weight: bold;
            color: #0f2f57;
            letter-spacing: 0.4px;
            margin: 0;
        }

        .no-ppd-box {
            display: inline-block;
            margin-top: 4px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #001b3e;
            background-color: #f1f5f9;
            padding: 2px 8px;
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
            width: 130px;
            font-weight: bold;
            color: #334155;
        }

        .meta-colon {
            width: 10px;
            text-align: center;
        }

        .items-table {
            border: 1px solid #64748b;
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

        .signature-table {
            margin-top: 18px;
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
            margin-top: 32px;
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
        $approvalsByTahap = $permohonan->approvals->keyBy('tahap');
        $signerKeuangan = $approvalsByTahap->get('keuangan') ?? $approvalsByTahap->get('disetujui_keuangan');
    @endphp

    {{-- HEADER KOP SURAT --}}
    <table class="header-table">
        <tr>
            <td style="width: 58%;">
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
            <td style="width: 42%;" class="title-box">
                <div class="title-main">BUKTI PENCAIRAN PERMOHONAN DANA (PPD)</div>
                <div class="no-ppd-box">Nomor PPD: {{ $pencairan->no_ppd }}</div>
            </td>
        </tr>
    </table>

    <hr class="header-rule">

    {{-- METADATA PENCAIRAN --}}
    <table class="meta-table">
        <tr>
            <td style="width: 52%; border-right: 1px solid #cbd5e1;">
                <table>
                    <tr>
                        <td class="meta-label">NOMOR PENGAJUAN</td>
                        <td class="meta-colon">:</td>
                        <td class="font-bold">{{ $permohonan->no_pengajuan }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">TANGGAL CAIR</td>
                        <td class="meta-colon">:</td>
                        <td class="font-bold">{{ $pencairan->tgl_cair->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">UNIT / DIVISI</td>
                        <td class="meta-colon">:</td>
                        <td>{{ strtoupper($permohonan->unit?->nama ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">PROGRAM</td>
                        <td class="meta-colon">:</td>
                        <td>{{ strtoupper($permohonan->program) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 48%; padding-left: 8px;">
                <table>
                    <tr>
                        <td class="meta-label">MATA ANGGARAN</td>
                        <td class="meta-colon">:</td>
                        <td class="font-bold">
                            {{ $permohonan->kodeAnggaran?->kode }}<br>
                            <span style="font-weight: normal; font-size: 8pt; color: #475569;">
                                {{ $permohonan->kodeAnggaran?->nama }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">NAMA KEGIATAN</td>
                        <td class="meta-colon">:</td>
                        <td>{{ strtoupper($permohonan->nama_kegiatan) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">SUMBER DANA</td>
                        <td class="meta-colon">:</td>
                        <td>{{ strtoupper($permohonan->sumberDana?->nama ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">STATUS DOKUMEN</td>
                        <td class="meta-colon">:</td>
                        <td class="font-bold" style="color: #0f2f57;">{{ strtoupper(str_replace('_', ' ', $pencairan->status)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TABEL RINCIAN PENCAIRAN --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">NO</th>
                <th style="width: 46%;">URAIAN</th>
                <th style="width: 26%;">PROGRAM UTAMA / ASNAF</th>
                <th style="width: 22%;">JUMLAH DISETUJUI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($permohonan->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left font-bold">{{ $item->uraian }}</td>
                    <td class="text-left">
                        {{ $item->programUtama?->nama ?? $permohonan->program }}
                        / {{ $item->asnaf?->nama ?? '-' }}
                    </td>
                    <td class="text-right font-bold" style="color: #047857;">
                        Rp {{ number_format($item->jumlah_disetujui ?? $item->jumlah_diminta, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="4">Tidak ada rincian item.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right uppercase">TOTAL PENCAIRAN:</td>
                <td class="text-right">Rp {{ number_format($pencairan->jumlah, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- KOTAK TANDA TANGAN 2 PIHAK --}}
    <table class="signature-table">
        <tr>
            <td class="sig-box">
                <div class="sig-header">DISETUJUI &amp; DICAIRKAN OLEH (BAGIAN KEUANGAN / TREASURY)</div>
                <div class="sig-name">
                    {{ $signerKeuangan?->signer_name ?: '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $signerKeuangan?->signer_jabatan ?: 'Bagian Keuangan / Treasury' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerKeuangan?->signed_at ? \Carbon\Carbon::parse($signerKeuangan->signed_at)->format('d/m/Y') : $pencairan->tgl_cair->format('d/m/Y') }}
                </div>
            </td>
            <td class="sig-box">
                <div class="sig-header">DITERIMA OLEH (PENERIMA DANA / PEMOHON)</div>
                <div class="sig-name">
                    {{ $permohonan->user?->name ?? '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $permohonan->user?->jabatan ?? 'Penerima Dana / Pemohon' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $pencairan->tgl_cair->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- FOOTER COPYRIGHT & DOKUMEN SISTEM --}}
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
