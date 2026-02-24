<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminHomologarInscricaoRequest;
use App\Http\Requests\AdminIndeferirInscricaoRequest;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoAvaliacao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricaoController extends Controller
{
    public function index(Request $request): View
    {
        ['status' => $status, 'search' => $search, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'editalId' => $editalId] = $this->extractIndexFilters($request);
        $perPageRaw = trim((string) $request->string('per_page', '30')->value());
        $perPageOptions = ['30', '50', '100', 'all'];
        if (! in_array($perPageRaw, $perPageOptions, true)) {
            $perPageRaw = '30';
        }
        $filtroAlterado = $editalId > 0
            || filled($status)
            || filled($search)
            || (bool) $dateStart
            || (bool) $dateEnd;

        $query = $this->buildIndexQuery($status, $search, $dateStart, $dateEnd, $editalId);

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        $inscricoes = $query
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.inscricoes.global', [
            'inscricoes' => $inscricoes,
            'status' => $status,
            'search' => $search,
            'dateStart' => $dateStart?->format('Y-m-d'),
            'dateEnd' => $dateEnd?->format('Y-m-d'),
            'editalId' => $editalId,
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
            'filtroAlterado' => $filtroAlterado,
            'editais' => Edital::query()->orderByDesc('periodo_inscricao_inicio')->get(['id', 'titulo']),
        ]);
    }

    public function exportXls(Request $request)
    {
        ['status' => $status, 'search' => $search, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'editalId' => $editalId] = $this->extractIndexFilters($request);

        $inscricoes = $this->buildIndexQuery($status, $search, $dateStart, $dateEnd, $editalId)
            ->get();

        $filename = 'inscricoes-'.now()->format('Ymd-His').'.xls';

        return response()
            ->view('admin.inscricoes.export_xls', [
                'inscricoes' => $inscricoes,
                'status' => $status,
                'search' => $search,
                'dateStart' => $dateStart?->format('d/m/Y'),
                'dateEnd' => $dateEnd?->format('d/m/Y'),
                'edital' => $editalId > 0 ? Edital::query()->find($editalId) : null,
            ])
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
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

    private function buildIndexQuery(string $status, string $search, ?Carbon $dateStart, ?Carbon $dateEnd, int $editalId)
    {
        return Inscricao::query()
            ->with('edital')
            ->when($editalId > 0, fn ($query) => $query->where('edital_id', $editalId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateStart && $dateEnd, fn ($query) => $query->whereBetween('submitted_at', [$dateStart, $dateEnd]))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('nome_completo', 'like', '%'.$search.'%')
                        ->orWhere('protocolo', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('cpf', 'like', '%'.$search.'%');
                });
            })
            ->latest('submitted_at');
    }

    /**
     * @return array{status:string,search:string,dateStart:?Carbon,dateEnd:?Carbon,editalId:int}
     */
    private function extractIndexFilters(Request $request): array
    {
        $status = $request->string('status')->value();
        $search = $request->string('q')->value();
        $dateStart = $this->parseDate($request->string('data_inicio')->value(), false);
        $dateEnd = $this->parseDate($request->string('data_fim')->value(), true);
        if ($dateStart && $dateEnd && $dateStart->gt($dateEnd)) {
            [$dateStart, $dateEnd] = [$dateEnd->copy()->startOfDay(), $dateStart->copy()->endOfDay()];
        }
        $editalId = (int) $request->integer('edital_id', 0);

        return compact('status', 'search', 'dateStart', 'dateEnd', 'editalId');
    }

    public function byEdital(Edital $edital, Request $request): View
    {
        $status = $request->string('status')->value();
        $search = $request->string('q')->value();
        $date = $request->string('data')->value();
        $perPageRaw = trim((string) $request->string('per_page', '30')->value());
        $perPageOptions = ['30', '50', '100', 'all'];
        if (! in_array($perPageRaw, $perPageOptions, true)) {
            $perPageRaw = '30';
        }

        $query = Inscricao::query()
            ->where('edital_id', $edital->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($date, fn ($query) => $query->whereDate('submitted_at', $date))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('nome_completo', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('cpf', 'like', '%'.$search.'%');
                });
            })
            ->latest('submitted_at');

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        $inscricoes = $query
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.inscricoes.index', [
            'edital' => $edital,
            'inscricoes' => $inscricoes,
            'status' => $status,
            'search' => $search,
            'date' => $date,
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function show(Inscricao $inscricao): View
    {
        $this->authorize('view', $inscricao);

        $inscricao->load([
            'edital.documentosRequeridos',
            'edital.docentesBanca',
            'documentos',
            'user',
            'decidedByUser',
            'avaliacoes.docente',
        ]);

        $avaliacoesByDocente = $inscricao->avaliacoes->keyBy('docente_id');
        $avaliacoesPainel = $inscricao->edital->docentesBanca
            ->map(function (User $docente) use ($avaliacoesByDocente) {
                /** @var InscricaoAvaliacao|null $avaliacao */
                $avaliacao = $avaliacoesByDocente->get($docente->id);

                return [
                    'docente' => $docente,
                    'status' => $avaliacao && $avaliacao->nota !== null ? 'AVALIADO' : 'PENDENTE',
                    'nota' => $avaliacao?->nota,
                    'comentario' => $avaliacao?->comentario,
                    'avaliado_at' => $avaliacao?->avaliado_at,
                ];
            })
            ->values();

        $mediaAvaliacoes = $inscricao->avaliacoes()
            ->whereNotNull('nota')
            ->avg('nota');

        return view('admin.inscricoes.show', [
            'inscricao' => $inscricao,
            'avaliacoesPainel' => $avaliacoesPainel,
            'mediaAvaliacoes' => $mediaAvaliacoes !== null ? number_format((float) $mediaAvaliacoes, 2, ',', '.') : null,
            'podeHomologar' => $inscricao->status === Inscricao::STATUS_RECEBIDA
                && $inscricao->possuiDocumentosObrigatorios(),
        ]);
    }

    public function salvarAvaliacao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);

        $data = $request->validate([
            'docente_id' => ['required', 'integer', 'exists:users,id'],
            'nota' => ['required', 'numeric', 'min:0', 'max:10'],
            'comentario' => ['nullable', 'string', 'max:2000'],
            'confirm_code_expected' => ['required', 'digits:2'],
            'confirm_code_input' => ['required', 'digits:2'],
        ], [
            'nota.required' => 'Informe a nota da avaliação.',
            'nota.numeric' => 'A nota deve ser numérica.',
            'nota.min' => 'A nota mínima é 0.',
            'nota.max' => 'A nota máxima é 10.',
            'confirm_code_input.required' => 'Informe o código de confirmação.',
            'confirm_code_input.digits' => 'O código de confirmação deve ter 2 dígitos.',
        ]);

        $this->assertDocenteNaBanca($inscricao, (int) $data['docente_id']);
        $this->assertConfirmCode($data['confirm_code_expected'], $data['confirm_code_input']);

        InscricaoAvaliacao::query()->updateOrCreate(
            [
                'inscricao_id' => $inscricao->id,
                'docente_id' => (int) $data['docente_id'],
            ],
            [
                'nota' => (float) $data['nota'],
                'comentario' => filled($data['comentario'] ?? null) ? trim((string) $data['comentario']) : null,
                'avaliado_at' => now(),
            ]
        );

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Avaliação atualizada com sucesso.');
    }

    public function limparAvaliacao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);

        $data = $request->validate([
            'docente_id' => ['required', 'integer', 'exists:users,id'],
            'confirm_code_expected' => ['required', 'digits:2'],
            'confirm_code_input' => ['required', 'digits:2'],
        ], [
            'confirm_code_input.required' => 'Informe o código de confirmação.',
            'confirm_code_input.digits' => 'O código de confirmação deve ter 2 dígitos.',
        ]);

        $this->assertDocenteNaBanca($inscricao, (int) $data['docente_id']);
        $this->assertConfirmCode($data['confirm_code_expected'], $data['confirm_code_input']);

        InscricaoAvaliacao::query()
            ->where('inscricao_id', $inscricao->id)
            ->where('docente_id', (int) $data['docente_id'])
            ->delete();

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Avaliação limpa com sucesso.');
    }

    public function enviarLembreteAvaliacao(Inscricao $inscricao, User $docente): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        $this->assertDocenteNaBanca($inscricao, $docente->id);

        $avaliacao = InscricaoAvaliacao::query()
            ->where('inscricao_id', $inscricao->id)
            ->where('docente_id', $docente->id)
            ->first();

        if ($avaliacao && $avaliacao->nota !== null) {
            throw ValidationException::withMessages([
                'avaliacao' => 'Lembrete disponível apenas para avaliações pendentes.',
            ]);
        }

        Mail::raw(
            "Olá {$docente->name},\n\nA inscrição {$inscricao->protocolo} do edital {$inscricao->edital?->titulo} ainda está pendente de avaliação.\n\nPor favor, acesse a plataforma para registrar sua avaliação.",
            function ($message) use ($docente): void {
                $message->to($docente->email)->subject('Lembrete de avaliação pendente');
            }
        );

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Lembrete enviado ao docente com sucesso.');
    }

    private function assertDocenteNaBanca(Inscricao $inscricao, int $docenteId): void
    {
        $isBanca = $inscricao->edital()
            ->whereHas('docentesBanca', fn ($q) => $q->where('users.id', $docenteId))
            ->exists();

        if (! $isBanca) {
            throw ValidationException::withMessages([
                'docente_id' => 'Docente não pertence à banca deste edital.',
            ]);
        }
    }

    private function assertConfirmCode(string $expected, string $input): void
    {
        if (trim($expected) !== trim($input)) {
            throw ValidationException::withMessages([
                'confirm_code_input' => 'Código de confirmação inválido.',
            ]);
        }
    }

    public function homologar(AdminHomologarInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        $flashPassword = null;
        $flashMessage = null;

        DB::transaction(function () use ($request, $inscricao, &$flashPassword, &$flashMessage): void {
            $inscricao = Inscricao::query()
                ->with(['edital.documentosRequeridos', 'documentos'])
                ->lockForUpdate()
                ->findOrFail($inscricao->id);

            if ($inscricao->status !== Inscricao::STATUS_RECEBIDA) {
                throw ValidationException::withMessages([
                    'status' => 'Apenas inscrições recebidas podem ser homologadas.',
                ]);
            }

            if (! $inscricao->possuiDocumentosObrigatorios()) {
                throw ValidationException::withMessages([
                    'documentos' => 'Não é possível homologar com documentos obrigatórios faltando.',
                ]);
            }

            $user = User::query()->where('email', $inscricao->email)->first();

            if ($user && $user->role !== User::ROLE_ALUNO) {
                throw ValidationException::withMessages([
                    'email' => 'Já existe usuário com esse e-mail e role diferente de aluno.',
                ]);
            }

            if (! $user) {
                $user = User::create([
                    'name' => $inscricao->nome_completo,
                    'email' => $inscricao->email,
                    'password' => Str::password(20),
                    'role' => User::ROLE_ALUNO,
                    'email_verified_at' => now(),
                ]);
            }

            $inscricao->update([
                'status' => Inscricao::STATUS_HOMOLOGADA,
                'decided_at' => now(),
                'decided_by' => $request->user()->id,
                'indeferimento_motivo' => null,
                'user_id' => $user->id,
            ]);

            $canUseResetFlow = config('mail.default') !== 'log';
            if ($canUseResetFlow && Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT) {
                $flashMessage = 'Inscrição homologada e link para definir senha enviado ao aluno.';

                return;
            }

            $flashPassword = Str::password(14);
            $user->forceFill([
                'password' => $flashPassword,
            ])->save();

            $flashMessage = 'Inscrição homologada. E-mail não configurado: use a senha temporária para repasse manual.';
        });

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', $flashMessage)
            ->with('senha_temporaria', $flashPassword);
    }

    public function indeferir(AdminIndeferirInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        if ($inscricao->status !== Inscricao::STATUS_RECEBIDA) {
            throw ValidationException::withMessages([
                'status' => 'Apenas inscrições recebidas podem ser indeferidas.',
            ]);
        }

        $inscricao->update([
            'status' => Inscricao::STATUS_INDEFERIDA,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
            'indeferimento_motivo' => $request->validated('indeferimento_motivo'),
        ]);

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Inscrição indeferida com sucesso.');
    }

    public function downloadDocumento(Inscricao $inscricao, InscricaoDocumento $doc)
    {
        $this->authorize('view', $inscricao);
        $this->authorize('view', $doc);

        abort_unless($doc->inscricao_id === $inscricao->id, 404);
        abort_unless(Storage::disk('local')->exists($doc->arquivo_path), 404);

        return Storage::disk('local')->download($doc->arquivo_path, $doc->original_name);
    }

    public function relatorioInscricoesRecebidasCsv(Edital $edital): StreamedResponse
    {
        return $this->csvPorStatus($edital, Inscricao::STATUS_RECEBIDA, 'inscricoes-recebidas');
    }

    public function relatorioInscricoesHomologadasCsv(Edital $edital): StreamedResponse
    {
        return $this->csvPorStatus($edital, Inscricao::STATUS_HOMOLOGADA, 'inscricoes-homologadas');
    }

    private function csvPorStatus(Edital $edital, string $status, string $prefix): StreamedResponse
    {
        $filename = sprintf('%s-edital-%d.csv', $prefix, $edital->id);

        return response()->streamDownload(function () use ($edital, $status): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'protocolo',
                'nome',
                'email',
                'cpf',
                'telefone',
                'status',
                'submitted_at',
                'decided_at',
            ], ';');

            Inscricao::query()
                ->where('edital_id', $edital->id)
                ->where('status', $status)
                ->orderBy('submitted_at')
                ->chunk(500, function ($chunk) use ($handle): void {
                    foreach ($chunk as $inscricao) {
                        fputcsv($handle, [
                            $inscricao->protocolo,
                            $inscricao->nome_completo,
                            $inscricao->email,
                            $inscricao->cpf,
                            $inscricao->telefone,
                            $inscricao->status,
                            optional($inscricao->submitted_at)->format('Y-m-d H:i:s'),
                            optional($inscricao->decided_at)->format('Y-m-d H:i:s'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
