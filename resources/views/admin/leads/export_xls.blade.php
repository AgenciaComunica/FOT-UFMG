<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Leads Selecionados</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #e2e8f0; font-weight: 700; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        p { margin: 0; color: #475569; }
    </style>
</head>
<body>
    <h1>Exportação de Leads Selecionados</h1>
    <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Último cadastro</th>
                <th>Último disparo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leads as $lead)
                <tr>
                    <td>{{ $lead->id }}</td>
                    <td>{{ $lead->nome }}</td>
                    <td>{{ $lead->email }}</td>
                    <td>{{ optional($lead->updated_at)->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ optional($lead->last_notified_at)->format('d/m/Y H:i') ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
