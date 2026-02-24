<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Resultado da inscrição</h2>
    <p><strong>Edital:</strong> {{ $inscricao->edital?->titulo }}</p>
    <p><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
    <p><strong>Status final:</strong> {{ $statusPublico }}</p>

    <p>Você pode acompanhar os dados na área pública:</p>
    <p>
        <a href="{{ $statusUrl }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
            Verificar status da inscrição
        </a>
    </p>
</body>
</html>

