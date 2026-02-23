<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreEditalRequest;
use App\Http\Requests\AdminUpdateEditalRequest;
use App\Models\Edital;
use App\Models\InscricaoDocumento;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EditalController extends Controller
{
    public function index(): View
    {
        return view('admin.editais.index', [
            'editais' => Edital::query()->latest('periodo_inscricao_inicio')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.editais.form', [
            'edital' => new Edital(),
            'tiposDocumentos' => InscricaoDocumento::TIPOS,
            'selectedDocumentos' => [],
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
            'periodo_inscricao_inicio' => $this->normalizeDateTime($data['periodo_inscricao_inicio'], false),
            'periodo_inscricao_fim' => $this->normalizeDateTime($data['periodo_inscricao_fim'], true),
        ]);

        $this->syncDocumentosRequeridos($edital, $data['documentos_requeridos']);

        return redirect()
            ->route('admin.editais.index')
            ->with('status', 'Edital criado com sucesso.');
    }

    public function edit(Edital $edital): View
    {
        $edital->load('documentosRequeridos');

        return view('admin.editais.form', [
            'edital' => $edital,
            'tiposDocumentos' => InscricaoDocumento::TIPOS,
            'selectedDocumentos' => $edital->documentosRequeridos->keyBy('tipo')->toArray(),
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
            'periodo_inscricao_inicio' => $this->normalizeDateTime($data['periodo_inscricao_inicio'], false),
            'periodo_inscricao_fim' => $this->normalizeDateTime($data['periodo_inscricao_fim'], true),
        ]);

        $this->syncDocumentosRequeridos($edital, $data['documentos_requeridos']);

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

    private function normalizeDateTime(string $value, bool $endOfDay): Carbon
    {
        $date = Carbon::parse($value);

        if (strlen(trim($value)) <= 10) {
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncDocumentosRequeridos(Edital $edital, array $items): void
    {
        $rows = collect($items)
            ->map(function (array $item, int $index): array {
                return [
                    'tipo' => $item['tipo'],
                    'descricao' => $item['descricao'] ?? null,
                    'obrigatorio' => (bool) ($item['obrigatorio'] ?? false),
                    'ordem' => (int) ($item['ordem'] ?? ($index + 1)),
                ];
            })
            ->sortBy('ordem')
            ->values()
            ->all();

        $edital->documentosRequeridos()->delete();
        $edital->documentosRequeridos()->createMany($rows);
    }
}
