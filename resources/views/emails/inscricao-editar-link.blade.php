<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Link para edição da inscrição</h2>

    <p>Você solicitou a edição da inscrição <strong>{{ $inscricao->protocolo }}</strong>.</p>
    <p>Este link expira em 24 horas e poderá ser usado uma única vez.</p>

    <p style="margin: 20px 0;">
        <a href="{{ $editUrl }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;">
            Editar inscrição
        </a>
    </p>

    <p>Se você não solicitou esta ação, desconsidere este e-mail.</p>
</body>
</html>

