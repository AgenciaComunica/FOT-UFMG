<?php

use App\Http\Middleware\EnsureUserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'newsletter',
        ]);

        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $mensagem = 'Você fez muitas tentativas em pouco tempo. Aguarde um minuto e tente novamente.';

            if ($request->routeIs('public.inscricao.email.reenviar')) {
                $mensagem = 'Você solicitou o reenvio do link de verificação muitas vezes em pouco tempo. Aguarde um minuto e tente novamente.';
            } elseif ($request->routeIs('public.inscricoes.enviar-link-edicao')) {
                $mensagem = 'Você solicitou o link de edição muitas vezes em pouco tempo. Aguarde um minuto e tente novamente.';
            } elseif ($request->routeIs('public.inscricoes.enviar-informacoes')) {
                $mensagem = 'Você solicitou o envio das informações completas muitas vezes em pouco tempo. Aguarde um minuto e tente novamente.';
            } elseif ($request->routeIs('public.inscricoes.verificar')) {
                $mensagem = 'Você realizou muitas consultas em pouco tempo. Aguarde um minuto e tente novamente.';
            }

            return redirect()
                ->back()
                ->with('status', $mensagem);
        });
    })->create();
