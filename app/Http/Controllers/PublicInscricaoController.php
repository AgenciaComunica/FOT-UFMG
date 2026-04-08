<?php

namespace App\Http\Controllers;

use App\Mail\DiskSpaceExceededAlertMail;
use App\Mail\InscricaoRecebidaMail;
use App\Http\Requests\PublicStoreInscricaoRequest;
use App\Http\Requests\PublicUpdateInscricaoRequest;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $this->ensureDiskSpaceAvailable();

        $validated = $request->validated();
        $edital->loadMissing('documentosRequeridos');
        $docsById = $edital->documentosRequeridos->keyBy('id');

        try {
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

                    $this->ensureDiskSpaceAvailable();

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
        } catch (\Throwable $e) {
            if ($this->isDiskFullException($e)) {
                $this->sendDiskSpaceAlert(0);

                return back()
                    ->withInput()
                    ->withErrors([
                        'documentos' => 'O sistema está indisponível no momento para envio de documentos. Tente novamente mais tarde ou entre em contato com a secretaria.',
                    ]);
            }

            throw $e;
        }

        $emailEnviado = $this->enviarEmailVerificacaoInscricao($inscricao);

        $redirect = redirect()->route('public.inscricao.confirmacao', [
            'edital' => $edital,
            'protocolo' => $inscricao->protocolo,
        ]);

        if (! $emailEnviado) {
            $redirect->with('status', 'Inscrição recebida, mas não foi possível enviar o e-mail de verificação agora. Tente reenviar em instantes.');
        }

        return $redirect;
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
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'resend_key' => ['required', 'string'],
        ], [
            'email.required' => 'Informe o e-mail cadastrado para confirmar o reenvio.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $emailInformado = mb_strtolower(trim((string) $validated['email']));
        $emailInscricao = mb_strtolower(trim((string) $inscricao->email));
        if ($emailInformado !== $emailInscricao) {
            return back()->with('status', 'O e-mail informado não confere com a inscrição. Se houver erro de cadastro, contate a secretaria.');
        }

        $key = (string) $validated['resend_key'];
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

        $emailEnviado = $this->enviarEmailVerificacaoInscricao($inscricao);

        if (! $emailEnviado) {
            return back()->with('status', 'Não foi possível reenviar o link agora. Tente novamente em instantes.');
        }

        return back()->with('status', 'Link de verificação reenviado com sucesso.');
    }

    public function editWithToken(Inscricao $inscricao, string $token): View|RedirectResponse
    {
        $erro = $this->validarTokenEdicao($inscricao, $token);
        if ($erro !== null) {
            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with('status', $erro);
        }

        $inscricao->loadMissing('edital.documentosRequeridos', 'documentos');

        return view('public.inscricao.edit', [
            'inscricao' => $inscricao,
            'edital' => $inscricao->edital,
            'editToken' => $token,
            'maxPdfKb' => config('inscricoes.max_pdf_kb', 10_240),
        ]);
    }

    public function updateWithToken(PublicUpdateInscricaoRequest $request, Inscricao $inscricao, string $token): RedirectResponse
    {
        $erro = $this->validarTokenEdicao($inscricao, $token);
        if ($erro !== null) {
            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with('status', $erro);
        }

        $validated = $request->validated();
        $inscricao->loadMissing('edital.documentosRequeridos', 'documentos');
        $docsById = $inscricao->edital->documentosRequeridos->keyBy('id');
        $this->ensureDiskSpaceAvailable();

        $emailAnterior = mb_strtolower(trim((string) $inscricao->email));
        $emailNovo = mb_strtolower(trim((string) ($validated['email'] ?? '')));
        $emailAlterado = $emailAnterior !== $emailNovo;

        try {
            DB::transaction(function () use ($request, $validated, $inscricao, $docsById, $emailAlterado): void {
                $tokenVerificacao = $emailAlterado
                    ? hash('sha256', Str::uuid().'|'.$validated['email'])
                    : $inscricao->email_verification_token;

                $inscricao->forceFill([
                    'nome_completo' => $validated['nome_completo'],
                    'email' => $validated['email'],
                    'cpf' => $validated['cpf'],
                    'telefone' => $validated['telefone'] ?? null,
                    'status' => Inscricao::STATUS_RECEBIDA,
                    'decided_at' => null,
                    'decided_by' => null,
                    'indeferimento_motivo' => null,
                    'email_verified_at' => $emailAlterado ? null : $inscricao->email_verified_at,
                    'email_verification_token' => $tokenVerificacao,
                    'verification_sent_at' => $emailAlterado ? now() : $inscricao->verification_sent_at,
                    'edit_link_used_at' => now(),
                    'edit_link_token' => null,
                    'edit_link_expires_at' => null,
                ])->save();

                $inscricao->edicoes()->create([
                    'motivo' => trim((string) $validated['motivo_edicao']),
                    'edited_at' => now(),
                ]);

                $inscricao->avaliacoes()->update([
                    'nota' => null,
                    'avaliacao_subjetiva' => null,
                    'comentario' => null,
                    'avaliado_at' => null,
                    'updated_at' => now(),
                ]);

                foreach ($request->file('documentos', []) as $docId => $file) {
                    if (! $file) {
                        continue;
                    }

                    $docConfig = $docsById->get((int) $docId);
                    if (! $docConfig) {
                        continue;
                    }

                    $this->ensureDiskSpaceAvailable();

                    $allowed = $docConfig->formatos_aceitos;
                    $defaultExt = $allowed[0] ?? 'pdf';
                    $extension = strtolower($file->getClientOriginalExtension() ?: $defaultExt);
                    if ($extension === 'jpeg') {
                        $extension = 'jpg';
                    }

                    $safeTipo = Str::slug($docConfig->tipo, '_');
                    $fileName = 'doc_'.$docConfig->id.'_'.$safeTipo.'.'.$extension;
                    $directory = 'inscricoes/'.$inscricao->id;
                    $newPath = $directory.'/'.$fileName;

                    Storage::disk('local')->putFileAs($directory, $file, $fileName);

                    $existing = $inscricao->documentos->firstWhere('tipo', $docConfig->tipo);
                    if ($existing instanceof InscricaoDocumento) {
                        if ($existing->arquivo_path !== $newPath && Storage::disk('local')->exists($existing->arquivo_path)) {
                            Storage::disk('local')->delete($existing->arquivo_path);
                        }

                        $existing->forceFill([
                            'arquivo_path' => $newPath,
                            'original_name' => $file->getClientOriginalName(),
                            'mime' => $file->getMimeType() ?? 'application/octet-stream',
                            'size' => $file->getSize(),
                            'uploaded_at' => now(),
                        ])->save();

                        continue;
                    }

                    $inscricao->documentos()->create([
                        'tipo' => $docConfig->tipo,
                        'arquivo_path' => $newPath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize(),
                        'uploaded_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            if ($this->isDiskFullException($e)) {
                $this->sendDiskSpaceAlert(0);

                return back()
                    ->withInput()
                    ->withErrors([
                        'documentos' => 'O sistema está indisponível no momento para envio de documentos. Tente novamente mais tarde ou entre em contato com a secretaria.',
                    ]);
            }

            throw $e;
        }

        if ($emailAlterado) {
            $this->enviarEmailVerificacaoInscricao($inscricao->fresh(['edital']));
        }

        return redirect()
            ->route('home', ['tab' => 'verificar'])
            ->with('status', 'Inscrição atualizada com sucesso.');
    }

    private function enviarEmailVerificacaoInscricao(Inscricao $inscricao): bool
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
            return true;
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de verificação da inscrição.', [
                'inscricao_id' => $inscricao->id,
                'protocolo' => $inscricao->protocolo,
                'email' => $inscricao->email,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function validarTokenEdicao(Inscricao $inscricao, string $token): ?string
    {
        $inscricao->loadMissing('edital');

        if (! $inscricao->edital?->publicado) {
            return 'Inscrição não disponível para edição.';
        }

        if (! $inscricao->edital?->isAberto()) {
            return 'O prazo do edital foi encerrado. Edição indisponível.';
        }

        if (! $inscricao->permiteEdicaoPublica()) {
            return 'A edição está disponível apenas para inscrições em homologação ou não homologadas.';
        }

        if (! filled($inscricao->edit_link_token)) {
            return 'Link de edição inválido.';
        }

        if (! hash_equals((string) $inscricao->edit_link_token, hash('sha256', $token))) {
            return 'Link de edição inválido.';
        }

        if ($inscricao->edit_link_used_at !== null) {
            return 'Este link de edição já foi utilizado.';
        }

        if ($inscricao->edit_link_expires_at === null || now()->gt($inscricao->edit_link_expires_at)) {
            return 'Este link de edição expirou. Solicite um novo link.';
        }

        return null;
    }

    private function ensureDiskSpaceAvailable(): void
    {
        $path = (string) config('filesystems.disks.local.root', storage_path('app/private'));
        $freeBytes = @disk_free_space($path);
        if ($freeBytes === false) {
            return;
        }

        $minimumBytes = max(0, (int) config('inscricoes.disk_min_free_mb', 512)) * 1024 * 1024;
        if ($minimumBytes <= 0 || $freeBytes > $minimumBytes) {
            return;
        }

        $this->sendDiskSpaceAlert((int) floor($freeBytes / 1024 / 1024));

        throw ValidationException::withMessages([
            'documentos' => 'O sistema está indisponível no momento para envio de documentos. Tente novamente mais tarde ou entre em contato com a secretaria.',
        ]);
    }

    private function sendDiskSpaceAlert(int $freeMb): void
    {
        $cooldownMinutes = max(5, (int) config('inscricoes.disk_alert_cooldown_minutes', 60));
        $cacheKey = 'disk-space-alert-mail-sent';
        if (! Cache::add($cacheKey, now()->timestamp, now()->addMinutes($cooldownMinutes))) {
            return;
        }

        $recipients = collect();
        $configured = trim((string) config('inscricoes.disk_alert_email', ''));
        if ($configured !== '') {
            $recipients = collect([$configured]);
        } else {
            $recipients = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->whereNotNull('email')
                ->pluck('email')
                ->filter();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DiskSpaceExceededAlertMail($freeMb));
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar alerta de espaço em disco.', [
                    'email' => $email,
                    'free_mb' => $freeMb,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function isDiskFullException(\Throwable $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'no space left on device')
            || str_contains($message, 'disk full')
            || str_contains($message, 'not enough space');
    }
}
