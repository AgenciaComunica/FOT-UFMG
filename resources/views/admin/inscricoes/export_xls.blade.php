<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #0f172a; }
        .header { margin-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo { width: 48px; height: 48px; object-fit: contain; }
        .title { font-size: 16px; font-weight: 700; }
        .subtitle { font-size: 12px; color: #475569; }
        .filters { margin-top: 8px; margin-bottom: 12px; }
        .filters span { display: inline-block; margin-right: 12px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        table.report th { background: #f1f5f9; text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width:56px;">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                    @endif
                </td>
                <td>
                    <div class="title">Relatório de Inscrições</div>
                    <div class="subtitle">Fisioterapia UFMG · Secretaria Digital</div>
                </td>
            </tr>
        </table>

        <div class="filters">
            <span><strong>Edital:</strong> {{ $edital?->titulo ?? 'Todos' }}</span>
            <span><strong>Status:</strong>
                @if ($status === 'HOMOLOGADA') Homologada
                @elseif($status === 'INDEFERIDA') Indeferida
                @elseif($status === 'RECEBIDA') Em Análise
                @else Todos @endif
            </span>
            <span><strong>Período:</strong> {{ $dateStart && $dateEnd ? "$dateStart até $dateEnd" : 'Todos' }}</span>
            <span><strong>Busca:</strong> {{ $search ?: '-' }}</span>
        </div>
    </div>

    <table class="report">
        <thead>
            <tr>
                <th>Protocolo</th>
                <th>Nome</th>
                <th>Email</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Edital</th>
                <th>Status</th>
                <th>Enviado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inscricoes as $inscricao)
                <tr>
                    <td>{{ $inscricao->protocolo }}</td>
                    <td>{{ $inscricao->nome_completo }}</td>
                    <td>{{ $inscricao->email }}</td>
                    <td>{{ $inscricao->cpf }}</td>
                    <td>{{ $inscricao->telefone }}</td>
                    <td>{{ $inscricao->edital?->titulo }}</td>
                    <td>
                        @if ($inscricao->status === 'HOMOLOGADA') Homologada
                        @elseif($inscricao->status === 'INDEFERIDA') Indeferida
                        @else Em Análise
                        @endif
                    </td>
                    <td>{{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhuma inscrição encontrada para os filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
