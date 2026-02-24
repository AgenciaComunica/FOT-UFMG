<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Inscrições encerradas para avaliação</h2>
    <p>O edital <strong>{{ $edital->titulo }}</strong> encerrou o período de inscrições.</p>
    <p>As candidaturas verificadas já estão disponíveis para avaliação na plataforma.</p>
    <p>
        <a href="{{ route('login') }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
            Acessar plataforma
        </a>
    </p>
</body>
</html>

