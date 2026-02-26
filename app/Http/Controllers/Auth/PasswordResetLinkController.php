<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $email = trim((string) $request->string('email')->value());
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Não encontramos uma conta com esse e-mail.']);
        }

        if (! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_DOCENTE], true)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Recuperação de senha disponível apenas para secretaria e docentes.']);
        }

        $status = Password::sendResetLink(
            ['email' => $email]
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Link de redefinição enviado com sucesso.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Não foi possível enviar o link de redefinição agora. Tente novamente em instantes.']);
    }
}
