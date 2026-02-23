<?php

namespace App\Providers;

use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Policies\InscricaoDocumentoPolicy;
use App\Policies\InscricaoPolicy;
use Illuminate\Support\Facades\Gate;
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
    }
}
