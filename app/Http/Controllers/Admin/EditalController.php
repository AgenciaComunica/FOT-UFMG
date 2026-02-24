<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreEditalRequest;
use App\Http\Requests\AdminUpdateEditalRequest;
use App\Models\Edital;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditalController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q')->value());
        $mes = (int) $request->integer('mes', 0);
        $ano = (int) $request->integer('ano', 0);
        $status = trim((string) $request->string('status')->value());
        $statusesPermitidos = ['RASCUNHO', 'AGUARDANDO', 'ABERTO', 'ENCERRADO'];
        if (! in_array($status, $statusesPermitidos, true)) {
            $status = '';
        }

        $query = Edital::query()
            ->with(['documentosRequeridos:id,edital_id,tipo,ordem'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($nested) use ($q) {
                    $nested
                        ->where('titulo', 'like', '%'.$q.'%')
                        ->orWhere('descricao', 'like', '%'.$q.'%');
                });
            })
            ->when($mes >= 1 && $mes <= 12, function ($builder) use ($mes) {
                $builder->where(function ($nested) use ($mes) {
                    $nested
                        ->whereMonth('periodo_inscricao_inicio', $mes)
                        ->orWhereMonth('periodo_inscricao_fim', $mes);
                });
            })
            ->when($ano >= 2000, function ($builder) use ($ano) {
                $builder->where(function ($nested) use ($ano) {
                    $nested
                        ->whereYear('periodo_inscricao_inicio', $ano)
                        ->orWhereYear('periodo_inscricao_fim', $ano);
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

        return view('admin.editais.index', [
            'editais' => $query->latest('periodo_inscricao_inicio')->paginate(12)->withQueryString(),
            'q' => $q,
            'mes' => $mes,
            'ano' => $ano,
            'status' => $status,
            'meses' => $meses,
            'anosDisponiveis' => $anosDisponiveis,
            'statusOptions' => $statusesPermitidos,
        ]);
    }

    public function create(): View
    {
        return view('admin.editais.form', [
            'edital' => new Edital(),
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

        return redirect()
            ->route('admin.editais.index')
            ->with('status', 'Edital criado com sucesso.');
    }

    public function edit(Edital $edital): View
    {
        $edital->load('documentosRequeridos');

        return view('admin.editais.form', [
            'edital' => $edital,
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
}
