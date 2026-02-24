<!DOCTYPE html>
<html lang="pt-BR">
<body style="font-family: Arial, sans-serif; color: #0f172a;">
    <h2>Acesso inicial da plataforma</h2>
    <p>Olá, {{ $nome }}.</p>
    <p>Seu acesso de docente foi criado.</p>
    <p><strong>E-mail:</strong> {{ $email }}</p>
    <p><strong>Senha temporária:</strong> {{ $senhaTemporaria }}</p>

    <p>
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
            Entrar na plataforma
        </a>
    </p>
    <p>Para redefinir a senha a qualquer momento, use:</p>
    <p><a href="{{ $forgotPasswordUrl }}">{{ $forgotPasswordUrl }}</a></p>
</body>
</html>

