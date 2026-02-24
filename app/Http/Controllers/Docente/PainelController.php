<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoAvaliacao;
use App\Models\InscricaoDocumento;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->string('status')->value() ?: 'PENDENTE';
        $search = trim((string) $request->string('q')->value());
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
        if (! in_array($status, ['PENDENTE', 'AVALIADO'], true)) {
            $status = 'PENDENTE';
        }

        $query = Inscricao::query()
            ->with(['edital', 'avaliacoes' => fn ($q) => $q->where('docente_id', $user->id)])
            ->whereHas('edital.docentesBanca', fn ($q) => $q->where('users.id', $user->id))
            ->when($editalId > 0, fn ($q) => $q->where('edital_id', $editalId))
            ->when($dateStart && $dateEnd, function ($q) use ($user, $dateStart, $dateEnd) {
                $q->whereHas('avaliacoes', function ($sub) use ($user, $dateStart, $dateEnd) {
                    $sub->where('docente_id', $user->id)
                        ->whereRaw('COALESCE(avaliado_at, updated_at) between ? and ?', [
                            $dateStart->toDateTimeString(),
                            $dateEnd->toDateTimeString(),
                        ]);
                });
            })
            ->when($search !== '', function ($q) use ($search, $user) {
                $q->where(function ($nested) use ($search, $user) {
                    $nested
                        ->where('nome_completo', 'like', '%'.$search.'%')
                        ->orWhere('protocolo', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhereHas('edital', fn ($editalQ) => $editalQ->where('titulo', 'like', '%'.$search.'%'))
                        ->orWhereHas('avaliacoes', function ($avaliacaoQ) use ($search, $user) {
                            $avaliacaoQ
                                ->where('docente_id', $user->id)
                                ->whereRaw('CAST(nota AS CHAR) LIKE ?', ['%'.$search.'%']);
                        });
                });
            })
            ->when($status === 'AVALIADO', function ($q) use ($user) {
                $q->whereHas('avaliacoes', function ($sub) use ($user) {
                    $sub->where('docente_id', $user->id)->whereNotNull('nota');
                });
            })
            ->when($status === 'PENDENTE', function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->whereDoesntHave('avaliacoes', fn ($s) => $s->where('docente_id', $user->id))
                        ->orWhereHas('avaliacoes', fn ($s) => $s->where('docente_id', $user->id)->whereNull('nota'));
                });
            })
            ->latest('submitted_at');

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        $inscricoes = $query->paginate($perPage)->withQueryString();
        $editais = Edital::query()
            ->whereHas('docentesBanca', fn ($q) => $q->where('users.id', $user->id))
            ->orderByDesc('periodo_inscricao_inicio')
            ->get(['id', 'titulo']);

        return view('docente.inscricoes.index', [
            'inscricoes' => $inscricoes,
            'status' => $status,
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
        $this->assertPodeAvaliar($inscricao, $user->id);

        $inscricao->load([
            'edital.documentosRequeridos',
            'documentos',
            'avaliacoes' => fn ($q) => $q->where('docente_id', $user->id),
        ]);

        $avaliacao = $inscricao->avaliacoes->first();
        $statusAvaliacao = $avaliacao && $avaliacao->nota !== null ? 'AVALIADO' : 'PENDENTE';

        return view('docente.inscricoes.show', [
            'inscricao' => $inscricao,
            'avaliacao' => $avaliacao,
            'statusAvaliacao' => $statusAvaliacao,
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

        return redirect()
            ->route('docente.inscricoes.show', $inscricao)
            ->with('status', 'Avaliação salva com sucesso.');
    }

    public function downloadDocumento(Request $request, Inscricao $inscricao, InscricaoDocumento $doc)
    {
        $user = $request->user();
        $this->assertPodeAvaliar($inscricao, $user->id);

        abort_unless($doc->inscricao_id === $inscricao->id, 404);
        abort_unless(Storage::disk('local')->exists($doc->arquivo_path), 404);

        return Storage::disk('local')->download($doc->arquivo_path, $doc->original_name);
    }

    private function assertPodeAvaliar(Inscricao $inscricao, int $docenteId): void
    {
        $ok = $inscricao->edital()
            ->whereHas('docentesBanca', fn ($q) => $q->where('users.id', $docenteId))
            ->exists();

        abort_unless($ok, 403);
    }
}
