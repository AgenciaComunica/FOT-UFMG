<?php

namespace App\Http\Controllers;

use App\Mail\InscricaoRecebidaMail;
use App\Http\Requests\PublicStoreInscricaoRequest;
use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicInscricaoController extends Controller
{
    public function create(Edital $edital): View
    {
        abort_unless($edital->isAberto(), 404);

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
                'email_verification_token' => hash('sha256', Str::uuid().'|'.$validated['email']),
                'verification_sent_at' => now(),
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

        $this->enviarEmailVerificacaoInscricao($inscricao);

        return redirect()->route('public.inscricao.confirmacao', [
            'edital' => $edital,
            'protocolo' => $inscricao->protocolo,
        ]);
    }

    public function confirmacao(Edital $edital, string $protocolo): View
    {
        $inscricao = Inscricao::query()
            ->where('edital_id', $edital->id)
            ->where('protocolo', $protocolo)
            ->first();
        abort_unless($inscricao, 404);

        return view('public.inscricao.confirmacao', [
            'edital' => $edital,
            'protocolo' => $protocolo,
            'inscricaoId' => $inscricao->id,
        ]);
    }

    public function avisoVerificacao(Inscricao $inscricao): View
    {
        $inscricao->loadMissing('edital');

        return view('public.inscricao.aviso-verificacao', [
            'inscricao' => $inscricao,
            'resendKey' => hash_hmac('sha256', $inscricao->id.'|'.$inscricao->email, (string) config('app.key')),
        ]);
    }

    public function verificarEmail(Inscricao $inscricao, string $token): RedirectResponse
    {
        if (! hash_equals((string) $inscricao->email_verification_token, $token)) {
            abort(404);
        }

        if (! $inscricao->email_verified_at) {
            $inscricao->forceFill([
                'email_verified_at' => now(),
                'email_verification_token' => null,
            ])->save();
        }

        return redirect()
            ->route('home', ['tab' => 'verificar'])
            ->with('status', 'E-mail da inscrição verificado com sucesso.');
    }

    public function reenviarVerificacao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $key = (string) $request->input('resend_key');
        $expected = hash_hmac('sha256', $inscricao->id.'|'.$inscricao->email, (string) config('app.key'));
        if (! hash_equals($expected, $key)) {
            abort(403);
        }

        if ($inscricao->email_verified_at) {
            return back()->with('status', 'E-mail desta inscrição já está verificado.');
        }

        $inscricao->forceFill([
            'email_verification_token' => hash('sha256', Str::uuid().'|'.$inscricao->email),
            'verification_sent_at' => now(),
        ])->save();

        $this->enviarEmailVerificacaoInscricao($inscricao);

        return back()->with('status', 'Link de verificação reenviado com sucesso.');
    }

    private function enviarEmailVerificacaoInscricao(Inscricao $inscricao): void
    {
        $inscricao->loadMissing('edital');

        $verificationUrl = route('public.inscricao.email.verificar', [
            'inscricao' => $inscricao,
            'token' => $inscricao->email_verification_token,
        ]);
        $statusUrl = route('home', ['tab' => 'verificar']);

        try {
            Mail::to($inscricao->email)->send(
                new InscricaoRecebidaMail($inscricao, $verificationUrl, $statusUrl)
            );
        } catch (\Throwable) {
        }
    }
}
