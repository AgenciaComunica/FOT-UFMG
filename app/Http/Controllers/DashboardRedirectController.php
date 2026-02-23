<?php

namespace App\Http\Controllers;

use App\Models\Edital;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->role === User::ROLE_ADMIN) {
            $edital = Edital::query()->latest('periodo_inscricao_inicio')->first();

            return $edital
                ? redirect()->route('admin.editais.inscricoes.index', $edital)
                : redirect()->route('admin.editais.index');
        }

        if ($user->role === User::ROLE_ALUNO) {
            return redirect()->route('aluno.painel');
        }

        abort(403);
    }
}
