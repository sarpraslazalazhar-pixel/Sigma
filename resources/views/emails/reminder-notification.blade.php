<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['judul'] ?? 'Pemberitahuan Sistem SIGMA' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #0F2F57; padding: 24px 28px; text-align: left;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">
                                            SIGMA
                                        </div>
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                            LAZ Al-Azhar • Pengelolaan Keuangan & Anggaran
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body Content --}}
                    <tr>
                        <td style="padding: 28px;">
                            <p style="margin: 0 0 16px 0; font-size: 14px; color: #334155; line-height: 1.5;">
                                <strong>Assalamu'alaikum Warahmatullahi Wabarakatuh,</strong><br>
                                Yth. <strong>{{ $penerima->name }}</strong>
                            </p>

                            {{-- Alert Box --}}
                            <div style="background-color: #f8fafc; border-left: 4px solid #1E60D5; border-radius: 8px; padding: 16px 18px; margin-bottom: 20px;">
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                                    {{ $data['judul'] ?? 'Pemberitahuan Sistem' }}
                                </div>
                                <div style="font-size: 13px; color: #475569; line-height: 1.6;">
                                    {{ $data['pesan'] ?? '-' }}
                                </div>
                            </div>

                            @if (!empty($data['jatuh_tempo']))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; font-size: 12px; color: #64748b; background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 10px 14px;">
                                    <tr>
                                        <td>
                                            <strong style="color: #991b1b;">Batas Jatuh Tempo:</strong>
                                            <span style="color: #b91c1c; font-weight: 600;">{{ \Carbon\Carbon::parse($data['jatuh_tempo'])->format('d/m/Y') }}</span>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            {{-- Action Button --}}
                            @if (!empty($data['url']))
                                <div style="text-align: center; margin: 28px 0 16px 0;">
                                    <a href="{{ $data['url'] }}" target="_blank" style="display: inline-block; background-color: #1E60D5; color: #ffffff; font-size: 13px; font-weight: 600; text-decoration: none; padding: 12px 28px; border-radius: 9999px; box-shadow: 0 2px 4px rgba(30, 96, 213, 0.2);">
                                        Buka Dokumen di SIGMA
                                    </a>
                                </div>
                                <div style="font-size: 11px; color: #94a3b8; text-align: center; margin-top: 8px; word-break: break-all;">
                                    Jika tombol di atas tidak berfungsi, buka tautan berikut:<br>
                                    <a href="{{ $data['url'] }}" style="color: #1E60D5; text-decoration: underline;">{{ $data['url'] }}</a>
                                </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 28px; font-size: 11px; color: #64748b; line-height: 1.5; text-align: center;">
                            <div>Email ini dibuat dan dikirimkan secara otomatis oleh sistem SIGMA LAZ Al-Azhar. Mohon tidak membalas langsung ke alamat email ini.</div>
                            <div style="margin-top: 6px; color: #94a3b8;">&copy; {{ date('Y') }} LAZ Al-Azhar. Hak cipta dilindungi.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
