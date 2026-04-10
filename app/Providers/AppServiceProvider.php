<?php

namespace App\Providers;

use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Policies\InscricaoDocumentoPolicy;
use App\Policies\InscricaoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Inscricao::class, InscricaoPolicy::class);
        Gate::policy(InscricaoDocumento::class, InscricaoDocumentoPolicy::class);

        RateLimiter::for('public-inscricoes-verificar', function (Request $request) {
            return Limit::perMinute(12)
                ->by('public-inscricoes-verificar|'.$request->ip())
                ->response(fn ($request, $headers) => redirect()
                    ->route('home', ['tab' => 'verificar'])
                    ->with('status', 'Muitas tentativas de consulta. Aguarde um minuto e tente novamente.')
                    ->withHeaders($headers)
                    ->setStatusCode(429));
        });

        RateLimiter::for('public-inscricao-enviar-informacoes', function (Request $request) {
            $inscricaoId = (string) optional($request->route('inscricao'))->getKey();

            return Limit::perMinute(4)
                ->by('public-inscricao-enviar-informacoes|'.$inscricaoId.'|'.$request->ip())
                ->response(fn ($request, $headers) => redirect()
                    ->route('home', ['tab' => 'verificar'])
                    ->with('status', 'Muitas tentativas para envio das informações completas. Aguarde um minuto e tente novamente.')
                    ->withHeaders($headers)
                    ->setStatusCode(429));
        });

        RateLimiter::for('public-inscricao-enviar-link-edicao', function (Request $request) {
            $inscricaoId = (string) optional($request->route('inscricao'))->getKey();

            return Limit::perMinute(4)
                ->by('public-inscricao-enviar-link-edicao|'.$inscricaoId.'|'.$request->ip())
                ->response(fn ($request, $headers) => redirect()
                    ->route('home', ['tab' => 'verificar'])
                    ->with('status', 'Muitas tentativas para solicitar o link de edição. Aguarde um minuto e tente novamente.')
                    ->withHeaders($headers)
                    ->setStatusCode(429));
        });

        RateLimiter::for('public-inscricao-reenviar-verificacao', function (Request $request) {
            $inscricaoId = (string) optional($request->route('inscricao'))->getKey();

            return Limit::perMinute(4)
                ->by('public-inscricao-reenviar-verificacao|'.$inscricaoId.'|'.$request->ip())
                ->response(fn ($request, $headers) => redirect()
                    ->back()
                    ->with('status', 'Muitas tentativas para reenviar o link de verificação. Aguarde um minuto e tente novamente.')
                    ->withHeaders($headers)
                    ->setStatusCode(429));
        });
    }
}
