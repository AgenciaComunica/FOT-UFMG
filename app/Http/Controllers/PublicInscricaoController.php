<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicStoreInscricaoRequest;
use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicInscricaoController extends Controller
{
    public function create(Edital $edital): View
    {
        $edital->load('documentosRequeridos');

        return view('public.inscricao.create', [
            'edital' => $edital,
            'honeypotField' => config('inscricoes.honeypot_field', 'website'),
            'maxPdfKb' => config('inscricoes.max_pdf_kb', 10_240),
        ]);
    }

    public function store(PublicStoreInscricaoRequest $request, Edital $edital): RedirectResponse
    {
        $validated = $request->validated();
        $edital->loadMissing('documentosRequeridos');
        $docsById = $edital->documentosRequeridos->keyBy('id');

        $inscricao = DB::transaction(function () use ($request, $validated, $edital, $docsById): Inscricao {
            $inscricao = Inscricao::create([
                'edital_id' => $edital->id,
                'protocolo' => (string) Str::uuid(),
                'nome_completo' => $validated['nome_completo'],
                'email' => $validated['email'],
                'cpf' => $validated['cpf'],
                'telefone' => $validated['telefone'] ?? null,
                'status' => Inscricao::STATUS_RECEBIDA,
                'submitted_at' => now(),
            ]);

            foreach ($request->file('documentos', []) as $docId => $file) {
                if (! $file) {
                    continue;
                }

                $docConfig = $docsById->get((int) $docId);
                if (! $docConfig) {
                    continue;
                }

                $allowed = $docConfig->formatos_aceitos;
                $defaultExt = $allowed[0] ?? 'pdf';
                $extension = strtolower($file->getClientOriginalExtension() ?: $defaultExt);
                if ($extension === 'jpeg') {
                    $extension = 'jpg';
                }
                $safeTipo = Str::slug($docConfig->tipo, '_');
                $fileName = 'doc_'.$docConfig->id.'_'.$safeTipo.'.'.$extension;
                $directory = 'inscricoes/'.$inscricao->id;

                Storage::disk('local')->putFileAs($directory, $file, $fileName);

                $inscricao->documentos()->create([
                    'tipo' => $docConfig->tipo,
                    'arquivo_path' => $directory.'/'.$fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?? 'application/octet-stream',
                    'size' => $file->getSize(),
                    'uploaded_at' => now(),
                ]);
            }

            return $inscricao;
        });

        return redirect()->route('public.inscricao.confirmacao', [
            'edital' => $edital,
            'protocolo' => $inscricao->protocolo,
        ]);
    }

    public function confirmacao(Edital $edital, string $protocolo): View
    {
        abort_unless(
            Inscricao::query()->where('edital_id', $edital->id)->where('protocolo', $protocolo)->exists(),
            404
        );

        return view('public.inscricao.confirmacao', [
            'edital' => $edital,
            'protocolo' => $protocolo,
        ]);
    }
}
