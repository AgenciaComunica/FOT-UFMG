<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreEditalRequest;
use App\Http\Requests\AdminUpdateEditalRequest;
use App\Mail\EditalPublicadoDocentesMail;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditalController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q')->value());
        $status = trim((string) $request->string('status')->value());
        $perPageRaw = trim((string) $request->string('per_page', '10')->value());
        $perPageOptions = ['10', '20', '50', '100', 'all'];
        if (! in_array($perPageRaw, $perPageOptions, true)) {
            $perPageRaw = '10';
        }
        $statusesPermitidos = ['RASCUNHO', 'AGUARDANDO', 'ABERTO', 'ENCERRADO', 'ARQUIVADO'];
        if (! in_array($status, $statusesPermitidos, true)) {
            $status = '';
        }

        $encerradosNaoArquivados = $this->encerradosNaoArquivadosQuery()
            ->withCount('inscricoes')
            ->orderByDesc('periodo_inscricao_fim')
            ->get(['id', 'titulo']);

        $editalPadraoGraficos = Edital::query()
            ->where('publicado', true)
            ->whereNull('archived_at')
            ->where('periodo_inscricao_inicio', '<=', now())
            ->where('periodo_inscricao_fim', '>=', now())
            ->orderByDesc('periodo_inscricao_inicio')
            ->first()
            ?? Edital::query()->whereNull('archived_at')->latest('periodo_inscricao_inicio')->first();

        $graficoEditalId = (int) $request->integer('grafico_edital_id', $editalPadraoGraficos?->id ?? 0);
        $graficoEdital = Edital::query()->find($graficoEditalId) ?? $editalPadraoGraficos;

        $cardsInicio = $this->parseDate($request->string('cards_inicio')->value(), false);
        $cardsFim = $this->parseDate($request->string('cards_fim')->value(), true);
        if ($cardsInicio && $cardsFim && $cardsInicio->gt($cardsFim)) {
            [$cardsInicio, $cardsFim] = [$cardsFim->copy()->startOfDay(), $cardsInicio->copy()->endOfDay()];
        }

        $graficoEditalDefaultId = (int) ($editalPadraoGraficos?->id ?? 0);
        $graficoFiltroAlterado = $graficoEditalId !== $graficoEditalDefaultId;

        $cardsFiltroAlterado =
            ($q !== '')
            || ($status !== '')
            || (bool) $cardsInicio
            || (bool) $cardsFim;

        $query = Edital::query()
            ->with([
                'documentosRequeridos:id,edital_id,tipo,ordem',
                'docentesBanca:id,name',
            ])
            ->withCount('inscricoes')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($nested) use ($q) {
                    $nested
                        ->where('titulo', 'like', '%'.$q.'%')
                        ->orWhere('descricao', 'like', '%'.$q.'%');
                });
            })
            ->when($cardsInicio && $cardsFim, function ($builder) use ($cardsInicio, $cardsFim) {
                $builder->where(function ($nested) use ($cardsInicio, $cardsFim) {
                    $nested
                        ->where('periodo_inscricao_inicio', '<=', $cardsFim)
                        ->where('periodo_inscricao_fim', '>=', $cardsInicio);
                });
            })
            ->when($status !== '', function ($builder) use ($status) {
                $now = now();

                return match ($status) {
                    'ARQUIVADO' => $builder->whereNotNull('archived_at'),
                    'RASCUNHO' => $builder->where('publicado', false)->whereNull('archived_at'),
                    'AGUARDANDO' => $builder->where('publicado', true)
                        ->whereNull('archived_at')
                        ->where('periodo_inscricao_inicio', '>', $now),
                    'ABERTO' => $builder->where('publicado', true)
                        ->whereNull('archived_at')
                        ->where('periodo_inscricao_inicio', '<=', $now)
                        ->where('periodo_inscricao_fim', '>=', $now),
                    'ENCERRADO' => $builder->where('publicado', true)
                        ->whereNull('archived_at')
                        ->where('periodo_inscricao_fim', '<', $now),
                    default => $builder,
                };
            });

        $anosInicio = Edital::query()
            ->selectRaw('DISTINCT YEAR(periodo_inscricao_inicio) as ano')
            ->orderByDesc('ano')
            ->pluck('ano');
        $anosFim = Edital::query()
            ->selectRaw('DISTINCT YEAR(periodo_inscricao_fim) as ano')
            ->orderByDesc('ano')
            ->pluck('ano');
        $anosDisponiveis = $anosInicio
            ->merge($anosFim)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        $janelaInicio = $graficoEdital?->periodo_inscricao_inicio?->copy()->startOfDay() ?? now()->startOfYear();
        $janelaFim = $graficoEdital?->periodo_inscricao_fim?->copy()->endOfDay() ?? now()->endOfYear();
        $serieTempoLabels = [];
        $serieTempoData = [];
        $graficoGranularidade = 'dia';

        if ($graficoEdital) {
            $diasIntervalo = $janelaInicio->diffInDays($janelaFim) + 1;
            $mesesIntervalo = $janelaInicio->diffInMonths($janelaFim);

            if ($mesesIntervalo > 18) {
                $graficoGranularidade = 'ano';
            } elseif ($diasIntervalo > 90) {
                $graficoGranularidade = 'mes';
            }

            /** @var Collection<string,int> $countMap */
            $countMap = Inscricao::query()
                ->where('edital_id', $graficoEdital->id)
                ->whereBetween('submitted_at', [$janelaInicio, $janelaFim])
                ->selectRaw(match ($graficoGranularidade) {
                    'ano' => "DATE_FORMAT(submitted_at, '%Y') as bucket, COUNT(*) as total",
                    'mes' => "DATE_FORMAT(submitted_at, '%Y-%m') as bucket, COUNT(*) as total",
                    default => "DATE(submitted_at) as bucket, COUNT(*) as total",
                })
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            if ($graficoGranularidade === 'ano') {
                $cursor = $janelaInicio->copy()->startOfYear();
                $limite = $janelaFim->copy()->endOfYear();
                while ($cursor->lte($limite)) {
                    $key = $cursor->format('Y');
                    $serieTempoLabels[] = $key;
                    $serieTempoData[] = (int) ($countMap[$key] ?? 0);
                    $cursor->addYear();
                }
            } elseif ($graficoGranularidade === 'mes') {
                $cursor = $janelaInicio->copy()->startOfMonth();
                $limite = $janelaFim->copy()->endOfMonth();
                while ($cursor->lte($limite)) {
                    $key = $cursor->format('Y-m');
                    $serieTempoLabels[] = ($meses[(int) $cursor->format('n')] ?? $cursor->format('m')).'/'.$cursor->format('Y');
                    $serieTempoData[] = (int) ($countMap[$key] ?? 0);
                    $cursor->addMonth();
                }
            } else {
                $cursor = $janelaInicio->copy();
                while ($cursor->lte($janelaFim)) {
                    $key = $cursor->format('Y-m-d');
                    $serieTempoLabels[] = $cursor->format('d/m');
                    $serieTempoData[] = (int) ($countMap[$key] ?? 0);
                    $cursor->addDay();
                }
            }
        }

        $statusCountMap = collect([
            'HOMOLOGADA' => 0,
            'INDEFERIDA' => 0,
            'RECEBIDA' => 0,
        ]);

        if ($graficoEdital) {
            $statusCountMap = Inscricao::query()
                ->where('edital_id', $graficoEdital->id)
                ->whereBetween('submitted_at', [$janelaInicio, $janelaFim])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->pipe(function ($counts) use ($statusCountMap) {
                    return $statusCountMap->mapWithKeys(function ($default, $status) use ($counts) {
                        return [$status => (int) ($counts[$status] ?? 0)];
                    });
                });
        }

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        return view('admin.editais.index', [
            'editais' => $query->latest('periodo_inscricao_inicio')->paginate($perPage)->withQueryString(),
            'q' => $q,
            'status' => $status,
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
            'meses' => $meses,
            'anosDisponiveis' => $anosDisponiveis,
            'statusOptions' => $statusesPermitidos,
            'graficoEditalId' => $graficoEdital?->id,
            'cardsInicio' => $cardsInicio?->format('Y-m-d'),
            'cardsFim' => $cardsFim?->format('Y-m-d'),
            'graficoFiltroAlterado' => $graficoFiltroAlterado,
            'cardsFiltroAlterado' => $cardsFiltroAlterado,
            'graficoEditais' => Edital::query()->orderByDesc('periodo_inscricao_inicio')->get(['id', 'titulo', 'periodo_inscricao_fim']),
            'graficoTempoLabels' => $serieTempoLabels,
            'graficoTempoData' => $serieTempoData,
            'graficoGranularidade' => $graficoGranularidade,
            'graficoStatusLabels' => ['Homologada', 'Indeferida', 'Em análise'],
            'graficoStatusData' => [
                (int) ($statusCountMap['HOMOLOGADA'] ?? 0),
                (int) ($statusCountMap['INDEFERIDA'] ?? 0),
                (int) ($statusCountMap['RECEBIDA'] ?? 0),
            ],
            'encerradosNaoArquivados' => $encerradosNaoArquivados,
            'publishedEditaisCount' => $this->publishedEditaisCount(),
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

    public function create(): View|RedirectResponse
    {
        if ($mensagem = $this->mensagemBloqueioPublicacao()) {
            return redirect()
                ->route('admin.editais.index')
                ->withErrors(['edital' => $mensagem]);
        }

        return view('admin.editais.form', [
            'edital' => new Edital(),
            'docentesDisponiveis' => User::query()
                ->where('role', User::ROLE_DOCENTE)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'ativo']),
            'bancaDocentesInitial' => old('banca_docentes', []),
            'documentosInitial' => old('documentos_requeridos', []),
            'formAction' => route('admin.editais.store'),
            'method' => 'POST',
            'publishedEditaisCount' => $this->publishedEditaisCount(),
        ]);
    }

    public function store(AdminStoreEditalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submitAction = (string) $request->input('submit_action', 'publish');
        $isDraft = $submitAction === 'draft';
        $publicado = match ($submitAction) {
            'draft' => false,
            'publish' => true,
            default => (bool) ($data['publicado'] ?? false),
        };
        $gotoNewDocente = $request->boolean('goto_new_docente');
        $inicio = $this->resolveDateTimeForPersist($data['periodo_inscricao_inicio'] ?? null, false);
        $fim = $this->resolveDateTimeForPersist($data['periodo_inscricao_fim'] ?? null, true);

        if ($publicado && ($mensagem = $this->mensagemBloqueioPublicacao())) {
            return back()->withInput()->withErrors(['edital' => $mensagem]);
        }

        $edital = Edital::create([
            'titulo' => $this->resolveTituloForPersist($data['titulo'] ?? null),
            'descricao' => $data['descricao'] ?? null,
            'publicado' => $publicado,
            'criterio_nota_corte' => $data['criterio_nota_corte'] ?? Edital::CORTE_APROVACAO_MANUAL,
            'nota_corte_fixa' => ($data['criterio_nota_corte'] ?? null) === Edital::CORTE_FIXA ? (float) ($data['nota_corte_fixa'] ?? 0) : null,
            'nota_corte_offset' => ($data['criterio_nota_corte'] ?? null) === Edital::CORTE_MEDIA_FLUTUANTE ? (float) ($data['nota_corte_offset'] ?? 0) : null,
            'numero_vagas' => ($data['criterio_nota_corte'] ?? null) === Edital::CORTE_NUMERO_VAGAS ? (int) ($data['numero_vagas'] ?? 0) : null,
            'periodo_inscricao_inicio' => $inicio,
            'periodo_inscricao_fim' => $fim->lt($inicio) ? $inicio->copy()->endOfDay() : $fim,
        ]);

        if ($request->hasFile('arquivo_edital')) {
            $file = $request->file('arquivo_edital');
            $directory = 'editais/'.$edital->id;
            $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $fileName = 'edital.'.$extension;

            Storage::disk('local')->putFileAs($directory, $file, $fileName);

            $edital->update([
                'arquivo_path' => $directory.'/'.$fileName,
                'arquivo_original_name' => $file->getClientOriginalName(),
                'arquivo_mime' => $file->getMimeType() ?? 'application/pdf',
                'arquivo_size' => $file->getSize(),
            ]);
        }

        $this->syncDocumentosRequeridos($edital, $data['documentos_requeridos'] ?? []);
        $this->syncBancaDocentes($edital, $data['banca_docentes'] ?? []);
        if ($publicado) {
            $this->notificarDocentesBancaPublicacao($edital);
        }

        if ($gotoNewDocente) {
            return redirect()
                ->route('admin.docentes.create', ['return_to' => route('admin.editais.edit', $edital)])
                ->with('status', 'Rascunho salvo. Cadastre o novo docente para voltar ao edital.');
        }

        $redirect = redirect()
            ->route('admin.editais.index')
            ->with('status', $publicado ? 'Edital criado com sucesso.' : 'Rascunho salvo com sucesso.');

        if ($publicado && $this->publishedEditaisCount() >= 2) {
            $redirect->with('warning', 'Você já possui 2 ou mais editais publicados. Fique atento ao espaço disponível em disco, pois se o limite for ultrapassado novas inscrições podem ser bloqueadas.');
        }

        return $redirect;
    }

    public function edit(Edital $edital): View
    {
        $edital->load(['documentosRequeridos', 'docentesBanca'])->loadCount('inscricoes');

        return view('admin.editais.form', [
            'edital' => $edital,
            'docentesDisponiveis' => User::query()
                ->where('role', User::ROLE_DOCENTE)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'ativo']),
            'bancaDocentesInitial' => old('banca_docentes', $edital->docentesBanca
                ->sortBy('pivot.ordem')
                ->map(fn ($docente) => [
                    'user_id' => $docente->id,
                    'aprovador' => (bool) ($docente->pivot->aprovador ?? false),
                ])
                ->values()
                ->all()),
            'documentosInitial' => old('documentos_requeridos', $edital->documentosRequeridos
                ->sortBy('ordem')
                ->map(fn ($doc) => [
                    'tipo' => $doc->tipo,
                    'formatos_aceitos' => $doc->formatos_aceitos,
                    'descricao' => $doc->descricao,
                    'obrigatorio' => (bool) $doc->obrigatorio,
                ])
                ->values()
                ->all()),
            'formAction' => route('admin.editais.update', $edital),
            'method' => 'PUT',
            'publishedEditaisCount' => $this->publishedEditaisCount($edital->id),
        ]);
    }

    public function update(AdminUpdateEditalRequest $request, Edital $edital): RedirectResponse
    {
        $data = $request->validated();
        $wasPublicado = (bool) $edital->publicado;
        $submitAction = (string) $request->input('submit_action', 'publish');
        $isDraft = $submitAction === 'draft';
        $publicado = match ($submitAction) {
            'draft' => false,
            'publish' => true,
            default => (bool) ($data['publicado'] ?? $edital->publicado),
        };
        $gotoNewDocente = $request->boolean('goto_new_docente');
        $inicio = $this->resolveDateTimeForPersist($data['periodo_inscricao_inicio'] ?? null, false, $edital->periodo_inscricao_inicio);
        $fim = $this->resolveDateTimeForPersist($data['periodo_inscricao_fim'] ?? null, true, $edital->periodo_inscricao_fim);

        if ($publicado && ($mensagem = $this->mensagemBloqueioPublicacao($edital->id))) {
            return back()->withInput()->withErrors(['edital' => $mensagem]);
        }

        $edital->update([
            'titulo' => $this->resolveTituloForPersist($data['titulo'] ?? null, $edital->titulo),
            'descricao' => $data['descricao'] ?? null,
            'publicado' => $publicado,
            'criterio_nota_corte' => $data['criterio_nota_corte'] ?? $edital->criterio_nota_corte ?? Edital::CORTE_APROVACAO_MANUAL,
            'nota_corte_fixa' => ($data['criterio_nota_corte'] ?? $edital->criterio_nota_corte) === Edital::CORTE_FIXA ? (float) ($data['nota_corte_fixa'] ?? $edital->nota_corte_fixa ?? 0) : null,
            'nota_corte_offset' => ($data['criterio_nota_corte'] ?? $edital->criterio_nota_corte) === Edital::CORTE_MEDIA_FLUTUANTE ? (float) ($data['nota_corte_offset'] ?? $edital->nota_corte_offset ?? 0) : null,
            'numero_vagas' => ($data['criterio_nota_corte'] ?? $edital->criterio_nota_corte) === Edital::CORTE_NUMERO_VAGAS ? (int) ($data['numero_vagas'] ?? $edital->numero_vagas ?? 0) : null,
            'periodo_inscricao_inicio' => $inicio,
            'periodo_inscricao_fim' => $fim->lt($inicio) ? $inicio->copy()->endOfDay() : $fim,
        ]);

        if ($request->hasFile('arquivo_edital')) {
            if ($edital->arquivo_path && Storage::disk('local')->exists($edital->arquivo_path)) {
                Storage::disk('local')->delete($edital->arquivo_path);
            }

            $file = $request->file('arquivo_edital');
            $directory = 'editais/'.$edital->id;
            $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $fileName = 'edital.'.$extension;

            Storage::disk('local')->putFileAs($directory, $file, $fileName);

            $edital->update([
                'arquivo_path' => $directory.'/'.$fileName,
                'arquivo_original_name' => $file->getClientOriginalName(),
                'arquivo_mime' => $file->getMimeType() ?? 'application/pdf',
                'arquivo_size' => $file->getSize(),
            ]);
        }

        $this->syncDocumentosRequeridos($edital, $data['documentos_requeridos'] ?? []);
        $this->syncBancaDocentes($edital, $data['banca_docentes'] ?? []);
        if (! $wasPublicado && $edital->publicado) {
            $this->notificarDocentesBancaPublicacao($edital);
        }

        if ($gotoNewDocente) {
            return redirect()
                ->route('admin.docentes.create', ['return_to' => route('admin.editais.edit', $edital)])
                ->with('status', 'Rascunho salvo. Cadastre o novo docente para voltar ao edital.');
        }

        $redirect = redirect()
            ->route('admin.editais.index')
            ->with('status', $publicado ? 'Edital atualizado com sucesso.' : 'Rascunho salvo com sucesso.');

        if ($publicado && $this->publishedEditaisCount($edital->id) >= 2) {
            $redirect->with('warning', 'Você já possui 2 ou mais editais publicados. Fique atento ao espaço disponível em disco, pois se o limite for ultrapassado novas inscrições podem ser bloqueadas.');
        }

        return $redirect;
    }

    public function destroy(Edital $edital): RedirectResponse
    {
        $inscricoesExcluidas = 0;

        DB::transaction(function () use ($edital, &$inscricoesExcluidas): void {
            $edital->loadMissing('inscricoes.documentos');
            $inscricoesExcluidas = $edital->inscricoes->count();

            foreach ($edital->inscricoes as $inscricao) {
                foreach ($inscricao->documentos as $doc) {
                    if (filled($doc->arquivo_path) && Storage::disk('local')->exists($doc->arquivo_path)) {
                        Storage::disk('local')->delete($doc->arquivo_path);
                    }
                }

                $directory = 'inscricoes/'.$inscricao->id;
                if (Storage::disk('local')->exists($directory)) {
                    Storage::disk('local')->deleteDirectory($directory);
                }

                $inscricao->documentos()->delete();
                $inscricao->avaliacoes()->delete();
                $inscricao->edicoes()->delete();
                $inscricao->delete();
            }

            if ($edital->arquivo_path && Storage::disk('local')->exists($edital->arquivo_path)) {
                Storage::disk('local')->delete($edital->arquivo_path);
            }

            $editalDirectory = 'editais/'.$edital->id;
            if (Storage::disk('local')->exists($editalDirectory)) {
                Storage::disk('local')->deleteDirectory($editalDirectory);
            }

            $edital->docentesBanca()->detach();
            $edital->documentosRequeridos()->delete();
            $edital->delete();
        });

        $mensagem = 'Edital excluído com sucesso.';
        if ($inscricoesExcluidas > 0) {
            $mensagem = 'Edital excluído com sucesso. '.$inscricoesExcluidas.' inscrição(ões) vinculada(s) também foram removidas.';
        }

        return redirect()
            ->route('admin.editais.index')
            ->with('status', $mensagem);
    }

    public function updatePublicacao(Request $request, Edital $edital): RedirectResponse
    {
        $publicado = $request->boolean('publicado');
        $wasPublicado = (bool) $edital->publicado;

        if ($edital->isArquivado()) {
            return back()->withErrors([
                'edital' => 'Editais arquivados não podem ser republicados.',
            ]);
        }

        if ($publicado) {
            if ($mensagem = $this->mensagemBloqueioPublicacao($edital->id)) {
                return back()->withErrors(['edital' => $mensagem]);
            }

            $missing = $this->missingCamposPublicacao($edital);
            if ($missing !== []) {
                return back()->withErrors([
                    'edital' => 'Não foi possível publicar. Preencha: '.implode(', ', $missing).'.',
                ]);
            }
        }

        $edital->update([
            'publicado' => $publicado,
        ]);
        if (! $wasPublicado && $publicado) {
            $this->notificarDocentesBancaPublicacao($edital);
        }

        $redirect = back()->with('status', 'Status do edital atualizado com sucesso.');

        if ($publicado && $this->publishedEditaisCount($edital->id) >= 2) {
            $redirect->with('warning', 'Você já possui 2 ou mais editais publicados. Fique atento ao espaço disponível em disco, pois se o limite for ultrapassado novas inscrições podem ser bloqueadas.');
        }

        return $redirect;
    }

    public function archive(Edital $edital): RedirectResponse
    {
        if ($edital->isArquivado()) {
            return back()->with('status', 'Este edital já está arquivado.');
        }

        if (! $edital->isEncerrado()) {
            return back()->withErrors([
                'edital' => 'Apenas editais encerrados podem ser arquivados.',
            ]);
        }

        DB::transaction(function () use ($edital): void {
            $edital->loadMissing('inscricoes.documentos');

            foreach ($edital->inscricoes as $inscricao) {
                foreach ($inscricao->documentos as $doc) {
                    if (filled($doc->arquivo_path) && Storage::disk('local')->exists($doc->arquivo_path)) {
                        Storage::disk('local')->delete($doc->arquivo_path);
                    }

                    $doc->forceFill([
                        'arquivo_path' => '',
                    ])->save();
                }

                $directory = 'inscricoes/'.$inscricao->id;
                if (Storage::disk('local')->exists($directory)) {
                    Storage::disk('local')->deleteDirectory($directory);
                }
            }

            if (filled($edital->arquivo_path) && Storage::disk('local')->exists($edital->arquivo_path)) {
                Storage::disk('local')->delete($edital->arquivo_path);
            }

            $editalDirectory = 'editais/'.$edital->id;
            if (Storage::disk('local')->exists($editalDirectory)) {
                Storage::disk('local')->deleteDirectory($editalDirectory);
            }

            $edital->forceFill([
                'arquivo_path' => null,
                'archived_at' => now(),
                'archived_by' => auth()->id(),
            ])->save();
        });

        return back()->with('status', 'Edital arquivado com sucesso. Os arquivos enviados pelos candidatos foram removidos do disco e os metadados foram preservados.');
    }

    public function downloadArquivo(Edital $edital): StreamedResponse
    {
        abort_unless($edital->arquivo_path && Storage::disk('local')->exists($edital->arquivo_path), 404);

        return Storage::disk('local')->download(
            $edital->arquivo_path,
            $edital->arquivo_original_name ?: 'edital.pdf'
        );
    }

    private function normalizeDateTime(string $value, bool $endOfDay): Carbon
    {
        $date = Carbon::parse($value);

        if (strlen(trim($value)) <= 10) {
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date;
    }

    private function resolveTituloForPersist(?string $titulo, ?string $fallback = null): string
    {
        $tituloNormalizado = trim((string) $titulo);
        if ($tituloNormalizado !== '') {
            return $tituloNormalizado;
        }

        if (filled($fallback)) {
            return trim((string) $fallback);
        }

        return 'Rascunho '.now()->format('d/m/Y H:i');
    }

    private function resolveDateTimeForPersist(?string $value, bool $endOfDay, ?Carbon $fallback = null): Carbon
    {
        if (filled($value)) {
            return $this->normalizeDateTime((string) $value, $endOfDay);
        }

        if ($fallback instanceof Carbon) {
            return $fallback->copy();
        }

        return $endOfDay ? now()->endOfDay() : now();
    }

    /**
     * @return array<int, string>
     */
    private function missingCamposPublicacao(Edital $edital): array
    {
        $missing = [];

        if (! filled($edital->titulo)) {
            $missing[] = 'Título';
        }

        if (! filled($edital->descricao)) {
            $missing[] = 'Descrição';
        }

        if (! $edital->periodo_inscricao_inicio) {
            $missing[] = 'Início da inscrição';
        }

        if (! $edital->periodo_inscricao_fim) {
            $missing[] = 'Fim da inscrição';
        }

        if (! $edital->arquivo_path) {
            $missing[] = 'Arquivo PDF do edital';
        }

        if (! filled($edital->criterio_nota_corte)) {
            $missing[] = 'Tipo da nota de corte';
        } elseif ($edital->criterio_nota_corte === Edital::CORTE_FIXA && $edital->nota_corte_fixa === null) {
            $missing[] = 'Nota de corte fixa';
        } elseif ($edital->criterio_nota_corte === Edital::CORTE_MEDIA_FLUTUANTE && $edital->nota_corte_offset === null) {
            $missing[] = 'Offset da média flutuante';
        } elseif ($edital->criterio_nota_corte === Edital::CORTE_NUMERO_VAGAS && (int) $edital->numero_vagas < 1) {
            $missing[] = 'Número de vagas';
        }

        if (! $edital->docentesBanca()->exists()) {
            $missing[] = 'Banca de Docentes (mínimo 1)';
        }

        return $missing;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncDocumentosRequeridos(Edital $edital, array $items): void
    {
        $rows = collect($items)
            ->map(function (array $item, int $index): array {
                return [
                    'tipo' => trim((string) $item['tipo']),
                    'formato_aceito' => collect($item['formatos_aceitos'] ?? [])
                        ->map(fn ($formato) => strtolower(trim((string) $formato)))
                        ->filter()
                        ->unique()
                        ->values()
                        ->implode(','),
                    'descricao' => $item['descricao'] ?? null,
                    'obrigatorio' => (bool) ($item['obrigatorio'] ?? false),
                    'ordem' => $index + 1,
                ];
            })
            ->filter(fn (array $item) => $item['tipo'] !== '' && $item['formato_aceito'] !== '')
            ->values()
            ->all();

        $edital->documentosRequeridos()->delete();
        $edital->documentosRequeridos()->createMany($rows);
    }

    /**
     * @param  array<int, mixed>  $docentesIds
     */
    private function syncBancaDocentes(Edital $edital, array $docentesRows): void
    {
        $clean = collect($docentesRows)
            ->map(function ($row) {
                return [
                    'user_id' => (int) (is_array($row) ? ($row['user_id'] ?? 0) : 0),
                    'aprovador' => (bool) (is_array($row) ? ($row['aprovador'] ?? false) : false),
                ];
            })
            ->filter(fn ($row) => $row['user_id'] > 0)
            ->unique('user_id')
            ->values();

        if ($clean->isEmpty()) {
            $edital->docentesBanca()->detach();
            return;
        }

        $validos = User::query()
            ->whereIn('id', $clean->pluck('user_id'))
            ->where('role', User::ROLE_DOCENTE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $payload = [];
        $ordem = 1;
        foreach ($clean as $row) {
            $id = (int) $row['user_id'];
            if (! in_array($id, $validos, true)) {
                continue;
            }
            $payload[$id] = [
                'ordem' => $ordem++,
                'aprovador' => (bool) $row['aprovador'],
            ];
        }

        $edital->docentesBanca()->sync($payload);
    }

    private function notificarDocentesBancaPublicacao(Edital $edital): void
    {
        $edital->loadMissing('docentesBanca');

        $docentes = $edital->docentesBanca
            ->filter(fn (User $docente) => filled($docente->email));

        foreach ($docentes as $docente) {
            try {
                Mail::to($docente->email)->send(new EditalPublicadoDocentesMail($edital));
            } catch (\Throwable) {
            }
        }
    }

    private function encerradosNaoArquivadosQuery(?int $ignoreId = null)
    {
        return Edital::query()
            ->where('publicado', true)
            ->whereNull('archived_at')
            ->where('periodo_inscricao_fim', '<', now())
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId));
    }

    private function mensagemBloqueioPublicacao(?int $ignoreId = null): ?string
    {
        $titulos = $this->encerradosNaoArquivadosQuery($ignoreId)
            ->orderByDesc('periodo_inscricao_fim')
            ->pluck('titulo')
            ->filter()
            ->values();

        if ($titulos->isEmpty()) {
            return null;
        }

        return 'Existe edital encerrado que ainda não foi arquivado. Arquive primeiro antes de abrir ou publicar um novo edital: '.$titulos->implode(', ').'.';
    }

    private function publishedEditaisCount(?int $ignoreId = null): int
    {
        return Edital::query()
            ->where('publicado', true)
            ->whereNull('archived_at')
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->count();
    }
}
