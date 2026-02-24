<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreEditalRequest;
use App\Http\Requests\AdminUpdateEditalRequest;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $statusesPermitidos = ['RASCUNHO', 'AGUARDANDO', 'ABERTO', 'ENCERRADO'];
        if (! in_array($status, $statusesPermitidos, true)) {
            $status = '';
        }

        $editalPadraoGraficos = Edital::query()
            ->where('publicado', true)
            ->where('periodo_inscricao_inicio', '<=', now())
            ->where('periodo_inscricao_fim', '>=', now())
            ->orderByDesc('periodo_inscricao_inicio')
            ->first()
            ?? Edital::query()->latest('periodo_inscricao_inicio')->first();

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
                    'RASCUNHO' => $builder->where('publicado', false),
                    'AGUARDANDO' => $builder->where('publicado', true)
                        ->where('periodo_inscricao_inicio', '>', $now),
                    'ABERTO' => $builder->where('publicado', true)
                        ->where('periodo_inscricao_inicio', '<=', $now)
                        ->where('periodo_inscricao_fim', '>=', $now),
                    'ENCERRADO' => $builder->where('publicado', true)
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
            'graficoStatusLabels' => ['Homologado', 'Não Homologado', 'Aguardando Homologação'],
            'graficoStatusData' => [
                (int) ($statusCountMap['HOMOLOGADA'] ?? 0),
                (int) ($statusCountMap['INDEFERIDA'] ?? 0),
                (int) ($statusCountMap['RECEBIDA'] ?? 0),
            ],
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

    public function create(): View
    {
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
        ]);
    }

    public function store(AdminStoreEditalRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $edital = Edital::create([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'publicado' => (bool) ($data['publicado'] ?? false),
            'criterio_nota_corte' => $data['criterio_nota_corte'],
            'nota_corte_fixa' => $data['criterio_nota_corte'] === Edital::CORTE_FIXA ? (float) $data['nota_corte_fixa'] : null,
            'nota_corte_offset' => $data['criterio_nota_corte'] === Edital::CORTE_MEDIA_FLUTUANTE ? (float) $data['nota_corte_offset'] : null,
            'numero_vagas' => $data['criterio_nota_corte'] === Edital::CORTE_NUMERO_VAGAS ? (int) $data['numero_vagas'] : null,
            'periodo_inscricao_inicio' => $this->normalizeDateTime($data['periodo_inscricao_inicio'], false),
            'periodo_inscricao_fim' => $this->normalizeDateTime($data['periodo_inscricao_fim'], true),
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

        return redirect()
            ->route('admin.editais.index')
            ->with('status', 'Edital criado com sucesso.');
    }

    public function edit(Edital $edital): View
    {
        $edital->load(['documentosRequeridos', 'docentesBanca']);

        return view('admin.editais.form', [
            'edital' => $edital,
            'docentesDisponiveis' => User::query()
                ->where('role', User::ROLE_DOCENTE)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'ativo']),
            'bancaDocentesInitial' => old('banca_docentes', $edital->docentesBanca
                ->sortBy('pivot.ordem')
                ->pluck('id')
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
        ]);
    }

    public function update(AdminUpdateEditalRequest $request, Edital $edital): RedirectResponse
    {
        $data = $request->validated();

        $edital->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'publicado' => (bool) ($data['publicado'] ?? false),
            'criterio_nota_corte' => $data['criterio_nota_corte'],
            'nota_corte_fixa' => $data['criterio_nota_corte'] === Edital::CORTE_FIXA ? (float) $data['nota_corte_fixa'] : null,
            'nota_corte_offset' => $data['criterio_nota_corte'] === Edital::CORTE_MEDIA_FLUTUANTE ? (float) $data['nota_corte_offset'] : null,
            'numero_vagas' => $data['criterio_nota_corte'] === Edital::CORTE_NUMERO_VAGAS ? (int) $data['numero_vagas'] : null,
            'periodo_inscricao_inicio' => $this->normalizeDateTime($data['periodo_inscricao_inicio'], false),
            'periodo_inscricao_fim' => $this->normalizeDateTime($data['periodo_inscricao_fim'], true),
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

        return redirect()
            ->route('admin.editais.index')
            ->with('status', 'Edital atualizado com sucesso.');
    }

    public function destroy(Edital $edital): RedirectResponse
    {
        if ($edital->inscricoes()->exists()) {
            return back()->withErrors([
                'edital' => 'Não é possível excluir um edital que já possui inscrições.',
            ]);
        }

        $edital->delete();

        return redirect()
            ->route('admin.editais.index')
            ->with('status', 'Edital excluído com sucesso.');
    }

    public function updatePublicacao(Request $request, Edital $edital): RedirectResponse
    {
        $publicado = $request->boolean('publicado');

        if ($publicado) {
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

        return back()->with('status', 'Status do edital atualizado com sucesso.');
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
    private function syncBancaDocentes(Edital $edital, array $docentesIds): void
    {
        $clean = collect($docentesIds)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($clean->isEmpty()) {
            $edital->docentesBanca()->detach();
            return;
        }

        $validos = User::query()
            ->whereIn('id', $clean)
            ->where('role', User::ROLE_DOCENTE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $payload = [];
        $ordem = 1;
        foreach ($clean as $id) {
            if (! in_array($id, $validos, true)) {
                continue;
            }
            $payload[$id] = ['ordem' => $ordem++];
        }

        $edital->docentesBanca()->sync($payload);
    }
}
