<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->role === User::ROLE_ADMIN) {
            return redirect()->route('admin.painel');
        }

        if ($user->role === User::ROLE_ALUNO) {
            return redirect()->route('aluno.painel');
        }

        abort(403);
    }
}
