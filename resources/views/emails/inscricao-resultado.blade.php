<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Resultado da inscrição</h2>
    <p><strong>Edital:</strong> {{ $inscricao->edital?->titulo }}</p>
    <p><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
    <p><strong>Status final:</strong> {{ $statusPublico }}</p>
    @if (in_array($inscricao->status, [\App\Models\Inscricao::STATUS_INDEFERIDA, \App\Models\Inscricao::STATUS_PRE_INDEFERIDA], true) && filled($inscricao->indeferimento_motivo))
        <p><strong>Motivo do indeferimento:</strong> {{ $inscricao->indeferimento_motivo }}</p>
    @endif

    <p>Você pode acompanhar os dados na área pública:</p>
    <p>
        <a href="{{ $statusUrl }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
            Verificar status da inscrição
        </a>
    </p>
</body>
</html>
