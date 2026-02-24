<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(): View
    {
        $inscricoes = auth()->user()
            ->inscricoes()
            ->with(['edital', 'documentos'])
            ->latest('submitted_at')
            ->get();

        return view('aluno.painel', [
            'inscricoes' => $inscricoes,
            'ultimaInscricao' => $inscricoes->first(),
        ]);
    }

    public function inscricoes(): View
    {
        $inscricoes = auth()->user()
            ->inscricoes()
            ->with('edital')
            ->latest('submitted_at')
            ->paginate(15);

        return view('aluno.inscricoes.index', [
            'inscricoes' => $inscricoes,
        ]);
    }

    public function show(Inscricao $inscricao): View
    {
        $this->authorize('view', $inscricao);

        $inscricao->load(['edital', 'documentos']);

        return view('aluno.inscricoes.show', [
            'inscricao' => $inscricao,
        ]);
    }

    public function downloadDocumento(InscricaoDocumento $doc)
    {
        $this->authorize('view', $doc);

        abort_unless(Storage::disk('local')->exists($doc->arquivo_path), 404);

        return Storage::disk('local')->download($doc->arquivo_path, $doc->original_name);
    }
}
