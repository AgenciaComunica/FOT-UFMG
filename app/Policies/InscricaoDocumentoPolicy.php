<?php

namespace App\Policies;

use App\Models\InscricaoDocumento;
use App\Models\User;

class InscricaoDocumentoPolicy
{
    public function view(User $user, InscricaoDocumento $doc): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return $user->role === User::ROLE_ALUNO && $doc->inscricao?->user_id === $user->id;
    }
}
