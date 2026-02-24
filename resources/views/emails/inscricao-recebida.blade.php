<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Inscrição recebida com sucesso</h2>
    <p><strong>Edital:</strong> {{ $inscricao->edital?->titulo }}</p>
    <p><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>

    <p>Para validar seu e-mail e liberar sua candidatura para avaliação, clique abaixo:</p>
    <p>
        <a href="{{ $verificationUrl }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
            Verificar e-mail da inscrição
        </a>
    </p>

    <p>Para acompanhar o status da inscrição:</p>
    <p>
        <a href="{{ $statusUrl }}" style="display:inline-block;padding:10px 14px;background:#0f766e;color:#fff;text-decoration:none;border-radius:6px;">
            Verificar status da inscrição
        </a>
    </p>
</body>
</html>

