<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Permohonan Dana - {{ $permohonan->no_pengajuan ?: 'DRAFT' }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 1.2cm 1.5cm 1.2cm 1.5cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #171c22;
            line-height: 1.3;
            margin: 0;
            padding: 0;
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
            font-size: 15pt;
            font-weight: bold;
            color: #0f2f57;
            letter-spacing: 0.5px;
            margin: 0;
            padding: 0;
        }

        .no-pengajuan-box {
            display: inline-block;
            border: 1.5px solid #0f2f57;
            padding: 4px 10px;
            margin-top: 4px;
            font-size: 8.5pt;
            font-weight: bold;
            color: #001b3e;
            background-color: #f1f5f9;
        }

        .draft-watermark {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            padding: 2px 8px;
            border: 1px solid #f87171;
            font-size: 8pt;
            margin-right: 6px;
        }

        .meta-table {
            margin-top: 10px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
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

        .meta-val {
            color: #0f172a;
        }

        .items-table {
            margin-top: 6px;
            border: 1px solid #64748b;
        }

        .items-table th {
            background-color: #0f2f57;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 6px;
            border: 1px solid #475569;
            text-align: center;
        }

        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 8.5pt;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }

        .total-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 1.5px solid #64748b;
            padding: 6px;
        }

        .terpakai-row td {
            background-color: #dbeafe;
            font-weight: bold;
            color: #001b3e;
            padding: 5px 8px;
            border: 1px solid #93c5fd;
            font-size: 8.5pt;
        }

        .signature-table {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .sig-box {
            width: 33.33%;
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
            margin-bottom: 45px;
        }

        .sig-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            text-align: center;
            text-decoration: underline;
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
        $signerAtasan = $approvalsByTahap->get('kadiv') ?: $approvalsByTahap->get('atasan');
        $signerKeuangan = $approvalsByTahap->get('keuangan');
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
            <td style="width: 45%;" class="title-box">
                @if ($permohonan->status === 'draft')
                    <span class="draft-watermark">DRAFT DOKUMEN</span>
                @endif
                <div class="title-main">FORM PERMOHONAN DANA</div>
                <div class="no-pengajuan-box">
                    Tanggal: {{ $permohonan->submitted_at ? $permohonan->submitted_at->format('d/m/Y') : ($permohonan->created_at ? $permohonan->created_at->format('d/m/Y') : '-') }} &nbsp;|&nbsp;
                    No. Pengajuan: {{ $permohonan->no_pengajuan ?: 'DRAFT' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- METADATA PENGAJUAN (2 KOLOM) --}}
    <table class="meta-table">
        <tr>
            <td style="width: 55%; border-right: 1px solid #cbd5e1; padding-right: 10px;">
                <table>
                    <tr>
                        <td class="meta-label">PROGRAM</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val">{{ strtoupper($permohonan->kodeAnggaran?->nama ?? $permohonan->program) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">NAMA KEGIATAN</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val font-bold">{{ strtoupper($permohonan->nama_kegiatan) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">DIVISI / UNIT</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val">{{ strtoupper($permohonan->unit?->nama ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">TANGGAL KEGIATAN</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val">
                            {{ $permohonan->tgl_mulai ? $permohonan->tgl_mulai->format('d/m/Y') : '-' }}
                            {{ $permohonan->tgl_selesai ? ' s.d ' . $permohonan->tgl_selesai->format('d/m/Y') : '' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">SUMBER DANA</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val">{{ strtoupper($permohonan->sumberDana?->nama ?? '-') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 45%; padding-left: 10px;">
                <table>
                    <tr>
                        <td class="meta-label" style="width: 120px;">MATA ANGGARAN</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val font-bold">
                            {{ $permohonan->kodeAnggaran?->kode ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">STATUS DOKUMEN</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val font-bold" style="color: #0f2f57;">
                            {{ strtoupper(str_replace('_', ' ', $permohonan->status)) }}
                        </td>
                    </tr>
                    @if ($permohonan->catatan_over)
                        <tr>
                            <td class="meta-label" style="color: #b45309;">STATUS OVER BUDGET</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val" style="color: #92400e; font-size: 7.5pt;">
                                Ya ({{ $permohonan->catatan_over }})
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- TABEL RINCIAN ITEM PERMOHONAN --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 38%;">URAIAN</th>
                <th style="width: 12%;">ASNAF</th>
                <th style="width: 18%;">PROGRAM UTAMA</th>
                <th style="width: 14%;">JUMLAH PERMOHONAN</th>
                <th style="width: 14%;">JUMLAH DISETUJUI</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($permohonan->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left font-bold">{{ $item->uraian }}</td>
                    <td class="text-center">{{ $item->asnaf?->nama ?? '-' }}</td>
                    <td class="text-left">{{ $item->programUtama?->nama ?? $permohonan->program }}</td>
                    <td class="text-right">Rp {{ number_format($item->jumlah_diminta, 0, ',', '.') }}</td>
                    <td class="text-right font-bold" style="color: #047857;">
                        {{ $item->jumlah_disetujui !== null ? 'Rp ' . number_format($item->jumlah_disetujui, 0, ',', '.') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right uppercase">TOTAL PERMOHONAN DANA:</td>
                <td class="text-right">Rp {{ number_format($permohonan->total_diminta, 0, ',', '.') }}</td>
                <td class="text-right font-bold" style="color: #047857;">
                    {{ $permohonan->total_disetujui !== null ? 'Rp ' . number_format($permohonan->total_disetujui, 0, ',', '.') : '-' }}
                </td>
            </tr>
            <tr class="terpakai-row">
                <td colspan="6" class="text-left">
                    <strong>TERPAKAI:</strong>
                    Rp {{ number_format($permohonan->total_disetujui ?? $permohonan->total_diminta, 0, ',', '.') }}
                    <span style="font-weight: normal; font-size: 7.5pt; color: #475569; margin-left: 10px;">
                        (Plafon terikat pada pos anggaran {{ $permohonan->kodeAnggaran?->kode }})
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- KOTAK TANDA TANGAN (3 KOLOM: Diperiksa Oleh, Disetujui Oleh, Dibuat Oleh) --}}
    <table class="signature-table">
        <tr>
            {{-- Kiri: Diperiksa Oleh --}}
            <td class="sig-box">
                <div class="sig-header">DIPERIKSA OLEH (KEPALA DIVISI SEKRETARIAT)</div>
                <div class="sig-name">
                    {{ $signerAtasan ? $signerAtasan->signer_name : '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $signerAtasan ? $signerAtasan->signer_jabatan : 'Kepala Divisi Sekretariat' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerAtasan && $signerAtasan->signed_at ? \Carbon\Carbon::parse($signerAtasan->signed_at)->format('d/m/Y') : '......................' }}
                </div>
            </td>

            {{-- Tengah: Disetujui Oleh --}}
            <td class="sig-box" style="margin-left: 10px; margin-right: 10px;">
                <div class="sig-header">DISETUJUI OLEH (BAGIAN KEUANGAN)</div>
                <div class="sig-name">
                    {{ $signerKeuangan ? $signerKeuangan->signer_name : '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $signerKeuangan ? $signerKeuangan->signer_jabatan : 'Manajer Anggaran & Treasury / Keuangan' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $signerKeuangan && $signerKeuangan->signed_at ? \Carbon\Carbon::parse($signerKeuangan->signed_at)->format('d/m/Y') : '......................' }}
                </div>
            </td>

            {{-- Kanan: Dibuat Oleh --}}
            <td class="sig-box">
                <div class="sig-header">DIBUAT OLEH (PEMOHON / PIC KEGIATAN)</div>
                <div class="sig-name">
                    {{ $permohonan->user?->name ?? '................................................' }}
                </div>
                <div class="sig-role">
                    {{ $permohonan->user?->jabatan ?? 'Pemohon Anggaran' }}
                </div>
                <div class="sig-date">
                    Tgl: {{ $permohonan->submitted_at ? $permohonan->submitted_at->format('d/m/Y') : ($permohonan->created_at ? $permohonan->created_at->format('d/m/Y') : '......................') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- FOOTER COPYRIGHT & DOKUMEN SISTEM --}}
    <table class="footer-table">
        <tr>
            <td style="width: 50%;" class="footer-text">
                Dicetak secara otomatis melalui Sistem SIGMA LAZ Al-Azhar pada {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB
            </td>
            <td style="width: 50%; text-align: right;" class="footer-text">
                Copyright @2024, www.alazharpeduli.or.id
            </td>
        </tr>
    </table>

</body>
</html>
