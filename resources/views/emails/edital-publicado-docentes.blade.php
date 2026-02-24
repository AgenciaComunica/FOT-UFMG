<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Edital Publicado</title>
</head>
<body style="font-family: Arial, sans-serif; color:#0f172a; line-height:1.5;">
    <h2 style="margin:0 0 12px;">Edital Publicado: {{ $edital->titulo }}</h2>

    <p>Você foi designado para a banca docente deste edital.</p>
    <p>Fique atento ao encerramento das inscrições para iniciar e concluir as avaliações dentro do prazo.</p>

    <p style="margin-top:16px;">
        <strong>Período de inscrição:</strong><br>
        Início: {{ optional($edital->periodo_inscricao_inicio)->format('d/m/Y H:i') }}<br>
        Fim: {{ optional($edital->periodo_inscricao_fim)->format('d/m/Y H:i') }}
    </p>

    <p style="margin-top:16px;">
        Atenciosamente,<br>
        Secretaria Fisioterapia UFMG
    </p>
</body>
</html>

