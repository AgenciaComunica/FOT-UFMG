<?php

namespace App\Http\Controllers;

use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicInscricaoController extends Controller
{
    public function create(): View
    {
        return view('inscricao.create', [
            'tiposDocumentos' => InscricaoDocumento::TIPOS,
            'honeypotField' => config('inscricoes.honeypot_field', 'website'),
            'maxPdfKb' => config('inscricoes.max_pdf_kb', 5120),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $honeypotField = config('inscricoes.honeypot_field', 'website');

        if (filled($request->input($honeypotField))) {
            throw ValidationException::withMessages([
                'form' => 'Nao foi possivel processar a inscricao.',
            ]);
        }

        $maxPdfKb = (int) config('inscricoes.max_pdf_kb', 5120);

        $rules = [
            'nome_completo' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'cpf' => ['required', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ];

        foreach (InscricaoDocumento::TIPOS as $tipo) {
            $rules['documentos.'.$tipo] = [
                'required',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:'.$maxPdfKb,
            ];
        }

        $validated = $request->validate($rules);

        $inscricao = null;

        DB::transaction(function () use ($request, $validated, &$inscricao) {
            $inscricao = Inscricao::create([
                'protocolo' => strtoupper(Str::random(12)),
                'nome_completo' => $validated['nome_completo'],
                'email' => $validated['email'],
                'cpf' => $validated['cpf'],
                'telefone' => $validated['telefone'] ?? null,
                'status' => Inscricao::STATUS_RECEBIDA,
                'submitted_at' => now(),
            ]);

            foreach (InscricaoDocumento::TIPOS as $tipo) {
                $file = $request->file('documentos.'.$tipo);
                $fileName = $tipo.'.pdf';
                $path = 'inscricoes/'.$inscricao->id.'/'.$fileName;

                Storage::disk('local')->putFileAs('inscricoes/'.$inscricao->id, $file, $fileName);

                $inscricao->documentos()->create([
                    'tipo' => $tipo,
                    'arquivo_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?? 'application/pdf',
                    'size' => $file->getSize(),
                    'uploaded_at' => now(),
                ]);
            }
        });

        return redirect()->route('inscricao.confirmacao', $inscricao->protocolo);
    }

    public function confirmacao(string $protocolo): View
    {
        abort_unless(Inscricao::query()->where('protocolo', $protocolo)->exists(), 404);

        return view('inscricao.confirmacao', compact('protocolo'));
    }
}
