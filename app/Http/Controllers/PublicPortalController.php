<?php

namespace App\Http\Controllers;

use App\Mail\InscricaoEditarLinkMail;
use App\Mail\InscricaoInformacoesCompletasMail;
use App\Models\Edital;
use App\Models\Inscricao;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicPortalController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value();
        if (! in_array($tab, ['editais', 'verificar'], true)) {
            $tab = 'editais';
        }

        $q = trim((string) $request->string('q')->value());
        $dateStart = $this->parseDate($request->string('data_inicio')->value(), false);
        $dateEnd = $this->parseDate($request->string('data_fim')->value(), true);
        if ($dateStart && $dateEnd && $dateStart->gt($dateEnd)) {
            [$dateStart, $dateEnd] = [$dateEnd->copy()->startOfDay(), $dateStart->copy()->endOfDay()];
        }

        $baseQuery = Edital::query()
            ->where('publicado', true)
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($nested) use ($q) {
                    $nested
                        ->where('titulo', 'like', '%'.$q.'%')
                        ->orWhere('descricao', 'like', '%'.$q.'%');
                });
            })
            ->when($dateStart && $dateEnd, function ($builder) use ($dateStart, $dateEnd) {
                $builder->where(function ($nested) use ($dateStart, $dateEnd) {
                    $nested
                        ->where('periodo_inscricao_inicio', '<=', $dateEnd)
                        ->where('periodo_inscricao_fim', '>=', $dateStart);
                });
            });

        $now = now();
        $abertos = (clone $baseQuery)
            ->where('periodo_inscricao_inicio', '<=', $now)
            ->where('periodo_inscricao_fim', '>=', $now)
            ->orderBy('periodo_inscricao_fim')
            ->get();

        $encerrados = (clone $baseQuery)
            ->where('periodo_inscricao_fim', '<', $now)
            ->orderByDesc('periodo_inscricao_fim')
            ->get();
        $proximos = (clone $baseQuery)
            ->where('periodo_inscricao_inicio', '>', $now)
            ->orderBy('periodo_inscricao_inicio')
            ->get();

        return view('welcome', [
            'tab' => $tab,
            'q' => $q,
            'dateStart' => $dateStart?->format('Y-m-d'),
            'dateEnd' => $dateEnd?->format('Y-m-d'),
            'abertos' => $abertos,
            'encerrados' => $encerrados,
            'proximos' => $proximos,
            'filtroAlterado' => $q !== '' || (bool) $dateStart || (bool) $dateEnd,
            'consultaResultados' => $request->session()->pull('consulta_resultados', []),
            'consultaTermo' => $request->session()->pull('consulta_termo', ''),
            'infoEmailError' => $request->session()->pull('info_email_error', ''),
            'infoEmailTargetId' => (int) $request->session()->pull('info_email_target_id', 0),
            'editLinkError' => $request->session()->pull('edit_link_error', ''),
            'editLinkTargetId' => (int) $request->session()->pull('edit_link_target_id', 0),
            'honeypotField' => config('inscricoes.honeypot_field', 'website'),
        ]);
    }

    public function verificarInscricao(Request $request): RedirectResponse
    {
        $honeypotField = (string) config('inscricoes.honeypot_field', 'website');
        $validated = $request->validate([
            'busca' => ['required', 'string', 'min:3', 'max:160'],
            $honeypotField => ['nullable', 'size:0'],
        ], [
            'busca.required' => 'Informe protocolo, e-mail ou CPF para consulta.',
            'busca.min' => 'Informe ao menos 3 caracteres para consulta.',
        ]);

        $rawTerm = trim((string) $validated['busca']);
        $resultados = $this->buildConsultaResultados($rawTerm);

        return redirect()
            ->route('home', ['tab' => 'verificar'])
            ->with([
                'consulta_resultados' => $resultados,
                'consulta_termo' => $rawTerm,
            ]);
    }

    public function enviarInformacoesCompletas(Request $request, Inscricao $inscricao): RedirectResponse
    {
        abort_unless($inscricao->edital?->publicado, 404);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'consulta_termo' => ['nullable', 'string', 'max:160'],
        ], [
            'email.required' => 'Informe o e-mail cadastrado para confirmar o envio.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $consultaTermo = trim((string) ($validated['consulta_termo'] ?? ''));
        if ($consultaTermo === '') {
            $consultaTermo = $inscricao->protocolo;
        }

        $resultados = $this->buildConsultaResultados($consultaTermo);
        $emailInformado = mb_strtolower(trim((string) $validated['email']));
        $emailInscricao = mb_strtolower(trim((string) $inscricao->email));

        if ($emailInformado !== $emailInscricao) {
            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with([
                    'consulta_resultados' => $resultados,
                    'consulta_termo' => $consultaTermo,
                    'info_email_error' => 'O e-mail informado não confere com a inscrição. Se houver erro de cadastro, contate a secretaria.',
                    'info_email_target_id' => $inscricao->id,
                ]);
        }

        try {
            Mail::to($inscricao->email)->send(new InscricaoInformacoesCompletasMail($inscricao));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de informações completas da inscrição.', [
                'inscricao_id' => $inscricao->id,
                'protocolo' => $inscricao->protocolo,
                'email' => $inscricao->email,
                'exception' => $e->getMessage(),
            ]);

            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with([
                    'consulta_resultados' => $resultados,
                    'consulta_termo' => $consultaTermo,
                    'info_email_error' => 'Não foi possível enviar o e-mail agora. Tente novamente em instantes.',
                    'info_email_target_id' => $inscricao->id,
                ]);
        }

        return redirect()
            ->route('home', ['tab' => 'verificar'])
            ->with([
                'consulta_resultados' => $resultados,
                'consulta_termo' => $consultaTermo,
                'status' => 'Informações completas enviadas para o e-mail cadastrado.',
            ]);
    }

    public function enviarLinkEdicao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        abort_unless($inscricao->edital?->publicado, 404);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'consulta_termo' => ['nullable', 'string', 'max:160'],
        ], [
            'email.required' => 'Informe o e-mail cadastrado para confirmar o envio.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $consultaTermo = trim((string) ($validated['consulta_termo'] ?? ''));
        if ($consultaTermo === '') {
            $consultaTermo = $inscricao->protocolo;
        }

        $resultados = $this->buildConsultaResultados($consultaTermo);
        $emailInformado = mb_strtolower(trim((string) $validated['email']));
        $emailInscricao = mb_strtolower(trim((string) $inscricao->email));

        if ($emailInformado !== $emailInscricao) {
            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with([
                    'consulta_resultados' => $resultados,
                    'consulta_termo' => $consultaTermo,
                    'edit_link_error' => 'O e-mail informado não confere com a inscrição. Se houver erro de cadastro, contate a secretaria.',
                    'edit_link_target_id' => $inscricao->id,
                ]);
        }

        if (! $inscricao->edital?->isAberto() || $inscricao->status !== Inscricao::STATUS_RECEBIDA) {
            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with([
                    'consulta_resultados' => $resultados,
                    'consulta_termo' => $consultaTermo,
                    'edit_link_error' => 'A edição está disponível apenas para inscrições em análise, durante o período aberto do edital.',
                    'edit_link_target_id' => $inscricao->id,
                ]);
        }

        $rawToken = Str::random(64);
        $hours = max(1, (int) config('inscricoes.edit_link_hours', 24));
        $expiresAt = now()->addHours($hours);

        $inscricao->forceFill([
            'edit_link_token' => hash('sha256', $rawToken),
            'edit_link_sent_at' => now(),
            'edit_link_expires_at' => $expiresAt,
            'edit_link_used_at' => null,
        ])->save();

        $editUrl = route('public.inscricoes.editar', [
            'inscricao' => $inscricao->id,
            'token' => $rawToken,
        ]);

        try {
            Mail::to($inscricao->email)->send(new InscricaoEditarLinkMail($inscricao->fresh(['edital']), $editUrl));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de link de edição da inscrição.', [
                'inscricao_id' => $inscricao->id,
                'protocolo' => $inscricao->protocolo,
                'email' => $inscricao->email,
                'exception' => $e->getMessage(),
            ]);

            return redirect()
                ->route('home', ['tab' => 'verificar'])
                ->with([
                    'consulta_resultados' => $resultados,
                    'consulta_termo' => $consultaTermo,
                    'edit_link_error' => 'Não foi possível enviar o link de edição agora. Tente novamente em instantes.',
                    'edit_link_target_id' => $inscricao->id,
                ]);
        }

        return redirect()
            ->route('home', ['tab' => 'verificar'])
            ->with([
                'consulta_resultados' => $resultados,
                'consulta_termo' => $consultaTermo,
                'status' => 'Link para edição enviado. O link expira em 24 horas e pode ser usado uma única vez.',
            ]);
    }

    public function downloadEdital(Edital $edital): StreamedResponse
    {
        abort_unless(
            $edital->publicado
            && $edital->hasArquivoEdital()
            && Storage::disk('local')->exists($edital->arquivo_path),
            404
        );

        return Storage::disk('local')->download(
            $edital->arquivo_path,
            $edital->arquivo_original_name ?: 'edital.pdf'
        );
    }

    private function parseDate(?string $value, bool $endOfDay): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function statusPublico(string $status): string
    {
        return match ($status) {
            Inscricao::STATUS_PRE_APROVADA => 'Pré-Aprovado',
            Inscricao::STATUS_PRE_INDEFERIDA => 'Pré-Indeferido',
            Inscricao::STATUS_HOMOLOGADA => 'Homologada',
            Inscricao::STATUS_INDEFERIDA => 'Indeferida',
            default => 'Em análise',
        };
    }

    private function maskEmail(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || ! str_contains($email, '@')) {
            return '-';
        }

        [$local, $domain] = explode('@', $email, 2);
        $localLen = mb_strlen($local);

        if ($localLen <= 2) {
            $maskedLocal = mb_substr($local, 0, 1).'*';
        } else {
            $maskedLocal = mb_substr($local, 0, 2).str_repeat('*', max(3, $localLen - 2));
        }

        return $maskedLocal.'@'.$domain;
    }

    private function maskCpf(?string $cpf): string
    {
        $digits = preg_replace('/\D+/', '', (string) $cpf) ?: '';
        if ($digits === '') {
            return '-';
        }

        $tail = substr($digits, -3);

        return '***.***.***-'.$tail;
    }

    private function maskNome(?string $nome): string
    {
        $nome = trim((string) $nome);
        if ($nome === '') {
            return '-';
        }

        $partes = preg_split('/\s+/', $nome) ?: [];
        if (count($partes) === 1) {
            $primeiro = $partes[0];
            return mb_substr($primeiro, 0, 1).str_repeat('*', max(2, mb_strlen($primeiro) - 1));
        }

        $primeiro = $partes[0];
        $ultimo = $partes[count($partes) - 1];

        return $primeiro.' '.mb_substr($ultimo, 0, 1).'.';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildConsultaResultados(string $rawTerm): array
    {
        $lowerTerm = mb_strtolower($rawTerm);
        $cpfDigits = preg_replace('/\D+/', '', $rawTerm) ?: '';

        $query = Inscricao::query()
            ->with('edital:id,titulo,publicado,periodo_inscricao_inicio,periodo_inscricao_fim')
            ->whereHas('edital', fn ($q) => $q->where('publicado', true))
            ->where(function ($q) use ($rawTerm, $lowerTerm, $cpfDigits) {
                $q->where('protocolo', $rawTerm)
                    ->orWhereRaw('LOWER(email) = ?', [$lowerTerm]);

                if ($cpfDigits !== '') {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', ''), ' ', '') = ?", [$cpfDigits]);
                }
            })
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return $query->map(function (Inscricao $inscricao): array {
            $canRequestEditLink = (bool) $inscricao->edital?->isAberto()
                && $inscricao->status === Inscricao::STATUS_RECEBIDA;

            return [
                'id' => $inscricao->id,
                'protocolo' => $inscricao->protocolo,
                'nome_completo' => $this->maskNome($inscricao->nome_completo),
                'email' => $this->maskEmail($inscricao->email),
                'cpf' => $this->maskCpf($inscricao->cpf),
                'status_key' => $inscricao->status,
                'status' => $this->statusPublico($inscricao->status),
                'email_verificado' => $inscricao->email_verified_at !== null,
                'resend_key' => hash_hmac('sha256', $inscricao->id.'|'.$inscricao->email, (string) config('app.key')),
                'edital' => $inscricao->edital?->titulo ?? '-',
                'submitted_at' => optional($inscricao->submitted_at)->format('d/m/Y H:i'),
                'decided_at' => optional($inscricao->decided_at)->format('d/m/Y H:i'),
                'can_request_edit_link' => $canRequestEditLink,
            ];
        })->values()->all();
    }
}
