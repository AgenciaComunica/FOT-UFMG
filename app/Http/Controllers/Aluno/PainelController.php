<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\InscricaoDocumento;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(): View
    {
        $inscricao = auth()->user()
            ->inscricoes()
            ->with('documentos')
            ->latest('submitted_at')
            ->first();

        return view('aluno.painel', [
            'inscricao' => $inscricao,
        ]);
    }

    public function downloadDocumento(InscricaoDocumento $doc)
    {
        $inscricao = $doc->inscricao;
        abort_unless($inscricao && $inscricao->user_id === auth()->id(), 403);
        abort_unless(Storage::disk('local')->exists($doc->arquivo_path), 404);

        return Storage::disk('local')->download($doc->arquivo_path, $doc->original_name);
    }
}
