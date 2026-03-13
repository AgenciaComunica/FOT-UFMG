<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoAvaliacao;
use App\Models\InscricaoDocumento;
use App\Services\InscricaoPreClassificacaoService;
use App\Services\InscricaoWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function __construct(
        private readonly InscricaoPreClassificacaoService $preClassificacaoService,
        private readonly InscricaoWorkflowService $workflowService,
    )
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $hasAprovadorAny = Edital::query()
            ->whereHas('docentesBanca', fn ($q) => $q
                ->where('users.id', $user->id)
                ->where('edital_docentes.aprovador', true))
            ->exists();
        $hasPendente = Inscricao::query()
            ->whereIn('status', [
                Inscricao::STATUS_HOMOLOGADA,
                Inscricao::STATUS_PRE_APROVADA,
                Inscricao::STATUS_PRE_INDEFERIDA,
            ])
            ->whereNotNull('email_verified_at')
            ->whereHas('edital.docentesBanca', fn ($q) => $q->where('users.id', $user->id))
            ->where(function ($sub) use ($user) {
                $sub->whereDoesntHave('avaliacoes', fn ($s) => $s->where('docente_id', $user->id))
                    ->orWhereHas('avaliacoes', fn ($s) => $s->where('docente_id', $user->id)->whereNull('nota'));
            })
            ->exists();

        $defaultTab = (! $hasPendente && $hasAprovadorAny) ? 'aprovacao' : 'pendente';
        $tab = strtolower(trim((string) $request->string('tab', $defaultTab)->value()));
        if (! in_array($tab, ['pendente', 'avaliado', 'aprovacao'], true)) {
            $tab = $defaultTab;
        }
        if ($tab === 'aprovacao' && ! $hasAprovadorAny) {
            $tab = $defaultTab;
        }
        $search = trim((string) $request->string('q')->value());
        $finalStatus = trim((string) $request->string('final_status')->value());
        if (! in_array($finalStatus, ['', Inscricao::STATUS_HOMOLOGADA, Inscricao::STATUS_PRE_APROVADA, Inscricao::STATUS_PRE_INDEFERIDA, Inscricao::STATUS_INDEFERIDA], true)) {
            $finalStatus = '';
        }
        $editalId = (int) $request->integer('edital_id', 0);
        $dateStart = $this->parseDate($request->string('data_inicio')->value(), false);
        $dateEnd = $this->parseDate($request->string('data_fim')->value(), true);
        if ($dateStart && $dateEnd && $dateStart->gt($dateEnd)) {
            [$dateStart, $dateEnd] = [$dateEnd->copy()->startOfDay(), $dateStart->copy()->endOfDay()];
        }
        $perPageRaw = trim((string) $request->string('per_page', '10')->value());
        $perPageOptions = ['10', '20', '50', '100', 'all'];
        if (! in_array($perPageRaw, $perPageOptions, true)) {
            $perPageRaw = '10';
        }
        $query = Inscricao::query()->with(['edital']);

        if ($tab === 'aprovacao') {
            $query
                ->with(['avaliacoes', 'edital.docentesBanca:id,name'])
                ->whereHas('edital.docentesBanca', fn ($q) => $q
                    ->where('users.id', $user->id)
                    ->where('edital_docentes.aprovador', true))
                ->when($finalStatus !== '', fn ($q) => $q->where('status', $finalStatus));
        } else {
            $query
                ->with(['avaliacoes' => fn ($q) => $q->where('docente_id', $user->id)])
                ->whereIn('status', [
                    Inscricao::STATUS_HOMOLOGADA,
                    Inscricao::STATUS_PRE_APROVADA,
                    Inscricao::STATUS_PRE_INDEFERIDA,
                ])
                ->whereNotNull('email_verified_at')
                ->whereHas('edital.docentesBanca', fn ($q) => $q->where('users.id', $user->id))
                ->when($tab === 'avaliado', function ($q) use ($user) {
                    $q->whereHas('avaliacoes', function ($sub) use ($user) {
                        $sub->where('docente_id', $user->id)->whereNotNull('nota');
                    });
                })
                ->when($tab === 'pendente', function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->whereDoesntHave('avaliacoes', fn ($s) => $s->where('docente_id', $user->id))
                            ->orWhereHas('avaliacoes', fn ($s) => $s->where('docente_id', $user->id)->whereNull('nota'));
                    });
                });
        }

        $query
            ->when($editalId > 0, fn ($q) => $q->where('edital_id', $editalId))
            ->when($dateStart && $dateEnd, function ($q) use ($user, $dateStart, $dateEnd, $tab) {
                if ($tab === 'aprovacao') {
                    $q->whereBetween('submitted_at', [$dateStart, $dateEnd]);
                    return;
                }

                $q->whereHas('avaliacoes', function ($sub) use ($user, $dateStart, $dateEnd) {
                    $sub->where('docente_id', $user->id)
                        ->whereRaw('COALESCE(avaliado_at, updated_at) between ? and ?', [
                            $dateStart->toDateTimeString(),
                            $dateEnd->toDateTimeString(),
                        ]);
                });
            })
            ->when($search !== '', function ($q) use ($search, $user, $tab) {
                $q->where(function ($nested) use ($search, $user, $tab) {
                    $nested
                        ->where('nome_completo', 'like', '%'.$search.'%')
                        ->orWhere('protocolo', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('edital', fn ($editalQ) => $editalQ->where('titulo', 'like', '%'.$search.'%'));

                    if ($tab !== 'aprovacao') {
                        $nested->orWhereHas('avaliacoes', function ($avaliacaoQ) use ($search, $user) {
                            $avaliacaoQ
                                ->where('docente_id', $user->id)
                                ->whereRaw('CAST(nota AS CHAR) LIKE ?', ['%'.$search.'%']);
                        });
                    }
                });
            })
            ->latest('submitted_at');

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        $inscricoes = $query->paginate($perPage)->withQueryString();
        $editais = Edital::query()
            ->whereHas('docentesBanca', function ($q) use ($user, $tab) {
                $q->where('users.id', $user->id);
                if ($tab === 'aprovacao') {
                    $q->where('edital_docentes.aprovador', true);
                }
            })
            ->orderByDesc('periodo_inscricao_inicio')
            ->get(['id', 'titulo']);

        return view('docente.inscricoes.index', [
            'inscricoes' => $inscricoes,
            'tab' => $tab,
            'hasAprovadorAny' => $hasAprovadorAny,
            'finalStatus' => $finalStatus,
            'search' => $search,
            'editalId' => $editalId,
            'editais' => $editais,
            'dateStart' => $dateStart?->format('Y-m-d'),
            'dateEnd' => $dateEnd?->format('Y-m-d'),
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
        ]);
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

    public function show(Request $request, Inscricao $inscricao): View
    {
        $user = $request->user();
        $isAprovador = $this->isAprovadorNoEdital($inscricao, $user->id);
        if ($isAprovador) {
            $this->assertPodeAcessarInscricao($inscricao, $user->id);
        } else {
            $this->assertPodeAvaliar($inscricao, $user->id);
        }

        $relations = [
            'edital.documentosRequeridos',
            'documentos',
        ];

        if ($isAprovador) {
            $relations[] = 'avaliacoes.docente';
            $relations[] = 'edital.docentesBanca:id,name';
        } else {
            $relations['avaliacoes'] = fn ($q) => $q->where('docente_id', $user->id);
        }

        $inscricao->load($relations);

        $avaliacao = $isAprovador
            ? $inscricao->avaliacoes->firstWhere('docente_id', $user->id)
            : $inscricao->avaliacoes->first();
        $statusAvaliacao = $avaliacao && $avaliacao->nota !== null ? 'AVALIADO' : 'PENDENTE';

        return view('docente.inscricoes.show', [
            'inscricao' => $inscricao,
            'avaliacao' => $avaliacao,
            'statusAvaliacao' => $statusAvaliacao,
            'isAprovadorNoEdital' => $isAprovador,
        ]);
    }

    public function salvarAvaliacao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $user = $request->user();
        $this->assertPodeAvaliar($inscricao, $user->id);

        $data = $request->validate([
            'nota' => ['required', 'numeric', 'min:0', 'max:10'],
            'avaliacao_subjetiva' => ['required', 'in:HOMOLOGAR,INDEFERIR,ABSTER'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ], [
            'nota.required' => 'Informe a nota da avaliação.',
            'nota.numeric' => 'A nota deve ser numérica.',
            'nota.min' => 'A nota mínima é 0.',
            'nota.max' => 'A nota máxima é 10.',
            'avaliacao_subjetiva.required' => 'Selecione a avaliação subjetiva.',
            'avaliacao_subjetiva.in' => 'Avaliação subjetiva inválida.',
        ]);

        InscricaoAvaliacao::query()->updateOrCreate(
            [
                'inscricao_id' => $inscricao->id,
                'docente_id' => $user->id,
            ],
            [
                'nota' => (float) $data['nota'],
                'avaliacao_subjetiva' => $data['avaliacao_subjetiva'],
                'comentario' => filled($data['comentario'] ?? null) ? trim((string) $data['comentario']) : null,
                'avaliado_at' => now(),
            ]
        );

        $this->preClassificacaoService->recalcular($inscricao->edital()->firstOrFail());

        return redirect()
            ->route('docente.inscricoes.show', $inscricao)
            ->with('status', 'Avaliação salva com sucesso.');
    }

    public function downloadDocumento(Request $request, Inscricao $inscricao, InscricaoDocumento $doc)
    {
        $user = $request->user();
        if ($this->isAprovadorNoEdital($inscricao, $user->id)) {
            $this->assertPodeAcessarInscricao($inscricao, $user->id);
        } else {
            $this->assertPodeAvaliar($inscricao, $user->id);
        }

        abort_unless($doc->inscricao_id === $inscricao->id, 404);
        abort_unless(Storage::disk('local')->exists($doc->arquivo_path), 404);

        return Storage::disk('local')->download($doc->arquivo_path, $doc->original_name);
    }

    public function definirVereditoFinal(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $user = $request->user();
        $this->assertPodeAcessarInscricao($inscricao, $user->id);
        $this->assertPodeDefinirVeredito($inscricao, $user->id);

        $data = $request->validate([
            'status' => ['required', 'in:HOMOLOGADA,INDEFERIDA,PRE_APROVADA,PRE_INDEFERIDA'],
            'indeferimento_motivo' => ['nullable', 'string', 'max:4000'],
        ], [
            'status.required' => 'Selecione um status válido.',
            'status.in' => 'Status inválido.',
        ]);

        if ($data['status'] === Inscricao::STATUS_INDEFERIDA && ! filled($data['indeferimento_motivo'] ?? null)) {
            throw ValidationException::withMessages([
                'indeferimento_motivo' => 'O motivo é obrigatório para definir como não homologada.',
            ]);
        }

        $temporaryPassword = $this->workflowService->applyStatus(
            $inscricao->fresh(['edital.documentosRequeridos', 'documentos']),
            $data['status'],
            $user->id,
            $data['indeferimento_motivo'] ?? null,
        );

        $redirect = redirect()
            ->route('docente.inscricoes.show', $inscricao)
            ->with('status', 'Veredito final atualizado com sucesso.');

        if ($temporaryPassword) {
            $redirect->with('senha_temporaria', $temporaryPassword);
        }

        return $redirect;
    }

    public function definirVereditoFinalLote(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'status' => ['required', 'in:HOMOLOGADA,INDEFERIDA,PRE_APROVADA,PRE_INDEFERIDA'],
            'indeferimento_motivo' => ['nullable', 'string', 'max:4000'],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'exists:inscricoes,id'],
        ]);
        if ($data['status'] === Inscricao::STATUS_INDEFERIDA && ! filled($data['indeferimento_motivo'] ?? null)) {
            throw ValidationException::withMessages([
                'indeferimento_motivo' => 'O motivo é obrigatório para definir como não homologada.',
            ]);
        }

        $ids = collect($data['selected_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_ids' => 'Selecione ao menos uma inscrição para aplicar a ação em lote.',
            ]);
        }

        $query = Inscricao::query();
        $query->whereIn('id', $ids);

        $updated = 0;
        $query->chunkById(100, function ($chunk) use (&$updated, $data, $user): void {
            foreach ($chunk as $inscricao) {
                if (! $this->isAprovadorNoEdital($inscricao, $user->id)) {
                    continue;
                }

                $this->workflowService->applyStatus(
                    $inscricao->fresh(['edital.documentosRequeridos', 'documentos']),
                    $data['status'],
                    $user->id,
                    $data['indeferimento_motivo'] ?? null,
                );
                $updated++;
            }
        });

        return back()->with('status', "Ação em lote aplicada em {$updated} inscrição(ões).");
    }

    private function assertPodeAvaliar(Inscricao $inscricao, int $docenteId): void
    {
        if (! in_array($inscricao->status, [Inscricao::STATUS_HOMOLOGADA, Inscricao::STATUS_PRE_APROVADA, Inscricao::STATUS_PRE_INDEFERIDA], true) || $inscricao->email_verified_at === null) {
            abort(403);
        }

        $this->assertPertenceBanca($inscricao, $docenteId);
    }

    private function assertPodeAcessarInscricao(Inscricao $inscricao, int $docenteId): void
    {
        if ($inscricao->email_verified_at === null) {
            abort(403);
        }

        $this->assertPertenceBanca($inscricao, $docenteId);
    }

    private function assertPertenceBanca(Inscricao $inscricao, int $docenteId): void
    {
        $ok = $inscricao->edital()
            ->whereHas('docentesBanca', fn ($q) => $q->where('users.id', $docenteId))
            ->exists();

        abort_unless($ok, 403);
    }

    private function isAprovadorNoEdital(Inscricao $inscricao, int $docenteId): bool
    {
        return $inscricao->edital()
            ->whereHas('docentesBanca', fn ($q) => $q
                ->where('users.id', $docenteId)
                ->where('edital_docentes.aprovador', true))
            ->exists();
    }

    private function assertPodeDefinirVeredito(Inscricao $inscricao, int $docenteId): void
    {
        abort_unless($this->isAprovadorNoEdital($inscricao, $docenteId), 403);
    }
}
