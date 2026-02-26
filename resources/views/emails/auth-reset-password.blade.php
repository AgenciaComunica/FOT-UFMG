<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a; margin: 0; padding: 24px; background: #f8fafc;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
        <tr>
            <td style="padding: 24px; border-bottom: 1px solid #e2e8f0;">
                <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" style="width: 180px; height: auto; display: block;">
            </td>
        </tr>
        <tr>
            <td style="padding: 24px;">
                <h2 style="margin: 0 0 12px; font-size: 22px; color: #0f172a;">Olá!</h2>
                <p style="margin: 0 0 10px; font-size: 15px; line-height: 1.5;">
                    Recebemos uma solicitação para redefinir a senha da sua conta no sistema FOT-UFMG.
                </p>

                <p style="margin: 22px 0;">
                    <a href="{{ $url }}" style="display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700;">
                        Redefinir senha
                    </a>
                </p>

                <p style="margin: 0 0 10px; font-size: 14px; color: #334155;">
                    Este link expira em <strong>{{ $expire }} minutos</strong>.
                </p>
                <p style="margin: 0; font-size: 14px; color: #334155;">
                    Se você não solicitou a redefinição, ignore este e-mail.
                </p>

                <p style="margin: 20px 0 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                    Se o botão não funcionar, copie e cole este link no navegador:<br>
                    <span style="word-break: break-all;">{{ $url }}</span>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>

