<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <title>{{ $news->title }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

    <!-- Wrapper -->
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <!-- Container -->
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">

                    <!-- ── HEADER ── -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1e40af 0%,#2563eb 50%,#0891b2 100%);border-radius:20px 20px 0 0;padding:36px 40px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <!-- Logo row -->
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background:rgba(255,255,255,0.15);border-radius:12px;padding:10px 14px;border:1px solid rgba(255,255,255,0.25);">
                                                    <span style="color:#ffffff;font-size:15px;font-weight:700;letter-spacing:0.5px;">📚 Presupuesto Escolar</span>
                                                </td>
                                            </tr>
                                        </table>
                                        <!-- Badge + Title -->
                                        <div style="margin-top:20px;">
                                            <span style="display:inline-block;background:rgba(255,255,255,0.2);color:#ffffff;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:5px 14px;border-radius:50px;border:1px solid rgba(255,255,255,0.3);">
                                                📰 &nbsp;Noticias
                                            </span>
                                        </div>
                                        <h1 style="margin:14px 0 0;color:#ffffff;font-size:26px;font-weight:800;line-height:1.3;letter-spacing:-0.5px;">
                                            {{ $news->title }}
                                        </h1>
                                        <!-- Date -->
                                        <p style="margin:10px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">
                                            📅 &nbsp;{{ $news->created_at->translatedFormat('d \d\e F \d\e Y') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── WHITE CARD ── -->
                    <tr>
                        <td style="background:#ffffff;padding:0 40px 8px;">

                            @if($fileUrl && $news->file_type === 'image')
                            <!-- Image -->
                            <div style="margin:28px 0 0;">
                                <img
                                    src="{{ $fileUrl }}"
                                    alt="{{ $news->title }}"
                                    style="width:100%;max-width:520px;border-radius:16px;display:block;object-fit:cover;border:1px solid #e2e8f0;"
                                />
                            </div>
                            @endif

                            @if($news->description)
                            <!-- Divider line -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:28px 0 0;">
                                <tr>
                                    <td style="border-top:1px solid #e2e8f0;"></td>
                                </tr>
                            </table>
                            <!-- Description -->
                            <div style="margin-top:24px;">
                                <p style="margin:0;color:#374151;font-size:16px;line-height:1.75;white-space:pre-line;">{{ $news->description }}</p>
                            </div>
                            @endif

                            @if($fileUrl && $news->file_type === 'pdf')
                            <!-- PDF CTA -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:28px 0 0;">
                                <tr>
                                    <td style="border-top:1px solid #e2e8f0;padding-top:24px;">
                                        <!-- PDF card -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                            style="background:#fef2f2;border:1px solid #fecaca;border-radius:16px;padding:20px 24px;">
                                            <tr>
                                                <td style="vertical-align:middle;width:48px;">
                                                    <div style="width:48px;height:48px;background:#fee2e2;border-radius:12px;text-align:center;line-height:48px;font-size:22px;">
                                                        📄
                                                    </div>
                                                </td>
                                                <td style="vertical-align:middle;padding-left:16px;">
                                                    <p style="margin:0;color:#991b1b;font-weight:700;font-size:14px;">Documento adjunto</p>
                                                    @if($news->original_filename)
                                                    <p style="margin:3px 0 0;color:#b91c1c;font-size:12px;">{{ $news->original_filename }}</p>
                                                    @endif
                                                </td>
                                                <td style="vertical-align:middle;text-align:right;">
                                                    <a href="{{ $fileUrl }}"
                                                        target="_blank"
                                                        style="display:inline-block;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;padding:10px 20px;border-radius:10px;">
                                                        Ver PDF
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Bottom spacing -->
                            <div style="height:32px;"></div>
                        </td>
                    </tr>

                    <!-- ── CTA BUTTON ── -->
                    <tr>
                        <td style="background:#ffffff;padding:0 40px 36px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                style="background:linear-gradient(135deg,#eff6ff,#f0f9ff);border:1px solid #bfdbfe;border-radius:16px;padding:24px;">
                                <tr>
                                    <td style="text-align:center;">
                                        <p style="margin:0 0 16px;color:#1e40af;font-weight:600;font-size:14px;">
                                            Ingresa al sistema para ver la noticia completa
                                        </p>
                                        <a href="{{ url('/noticias') }}"
                                            style="display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:12px 32px;border-radius:12px;">
                                            Ver en el sistema →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── FOOTER ── -->
                    <tr>
                        <td style="background:#1e293b;border-radius:0 0 20px 20px;padding:28px 40px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <p style="margin:0;color:#94a3b8;font-size:13px;font-weight:600;">
                                            📚 &nbsp;Presupuesto Escolar
                                        </p>
                                        <p style="margin:6px 0 0;color:#64748b;font-size:12px;line-height:1.6;">
                                            Este mensaje fue enviado automáticamente por el sistema.<br/>
                                            Por favor, no respondas a este correo.
                                        </p>
                                    </td>
                                    <td style="text-align:right;vertical-align:middle;">
                                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#2563eb,#0891b2);border-radius:10px;display:inline-block;text-align:center;line-height:40px;font-size:18px;">
                                            📰
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
