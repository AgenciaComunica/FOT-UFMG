<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InscricaoController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();
        $search = $request->string('q')->value();

        $inscricoes = Inscricao::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('nome_completo', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('secretaria.inscricoes.index', [
            'inscricoes' => $inscricoes,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(Inscricao $inscricao): View
    {
        $inscricao->load(['documentos', 'user', 'decidedByUser']);

        return view('secretaria.inscricoes.show', compact('inscricao'));
    }

    public function aprovar(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $generatedPassword = null;

        DB::transaction(function () use ($request, &$generatedPassword, $inscricao) {
            $inscricao = Inscricao::query()->lockForUpdate()->findOrFail($inscricao->id);

            if ($inscricao->status !== Inscricao::STATUS_RECEBIDA) {
                throw ValidationException::withMessages([
                    'status' => 'Apenas inscricoes recebidas podem ser aprovadas.',
                ]);
            }

            $user = User::query()->where('email', $inscricao->email)->first();

            if ($user) {
                if ($user->role !== User::ROLE_ALUNO) {
                    throw ValidationException::withMessages([
                        'email' => 'Ja existe usuario com esse email e role diferente de aluno.',
                    ]);
                }
            } else {
                $generatedPassword = Str::password(14);
                $user = User::create([
                    'name' => $inscricao->nome_completo,
                    'email' => $inscricao->email,
                    'password' => $generatedPassword,
                    'role' => User::ROLE_ALUNO,
                ]);
            }

            $inscricao->update([
                'status' => Inscricao::STATUS_APROVADA,
                'decided_at' => now(),
                'decided_by' => $request->user()->id,
                'rejection_reason' => null,
                'user_id' => $user->id,
            ]);
        });

        $message = 'Inscricao aprovada com sucesso.';
        if ($generatedPassword) {
            $message .= ' Senha inicial (mostrar uma vez): '.$generatedPassword;
        }

        return redirect()
            ->route('secretaria.inscricoes.show', $inscricao)
            ->with('status', $message);
    }

    public function rejeitar(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5'],
        ]);

        if ($inscricao->status !== Inscricao::STATUS_RECEBIDA) {
            throw ValidationException::withMessages([
                'status' => 'Apenas inscricoes recebidas podem ser rejeitadas.',
            ]);
        }

        $inscricao->update([
            'status' => Inscricao::STATUS_REJEITADA,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return redirect()
            ->route('secretaria.inscricoes.show', $inscricao)
            ->with('status', 'Inscricao rejeitada com sucesso.');
    }

    public function downloadDocumento(Inscricao $inscricao, InscricaoDocumento $doc)
    {
        abort_unless($doc->inscricao_id === $inscricao->id, 404);
        abort_unless(Storage::disk('local')->exists($doc->arquivo_path), 404);

        return Storage::disk('local')->download($doc->arquivo_path, $doc->original_name);
    }
}
