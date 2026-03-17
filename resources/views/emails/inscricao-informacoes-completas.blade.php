<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Informações completas da inscrição</h2>

    <p><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
    <p><strong>Status:</strong> {{ match($inscricao->status) {
        \App\Models\Inscricao::STATUS_PRE_APROVADA => 'Pré-Aprovado',
        \App\Models\Inscricao::STATUS_PRE_INDEFERIDA => 'Pré-Indeferido',
        \App\Models\Inscricao::STATUS_HOMOLOGADA => 'Homologada',
        \App\Models\Inscricao::STATUS_INDEFERIDA => 'Indeferida',
        default => 'Em análise',
    } }}</p>
    <p><strong>E-mail verificado:</strong> {{ $inscricao->email_verified_at ? 'Sim' : 'Não' }}</p>
    <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
    <p><strong>Edital:</strong> {{ $inscricao->edital?->titulo ?? '-' }}</p>
    <p><strong>E-mail:</strong> {{ $inscricao->email }}</p>
    <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
    <p><strong>Enviado em:</strong> {{ optional($inscricao->submitted_at)->format('d/m/Y H:i') ?? '-' }}</p>
    <p><strong>Decidido em:</strong> {{ optional($inscricao->decided_at)->format('d/m/Y H:i') ?? '-' }}</p>
</body>
</html>
