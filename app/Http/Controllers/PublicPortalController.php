<?php

namespace App\Http\Controllers;

use App\Models\Edital;
use App\Models\Inscricao;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $lowerTerm = mb_strtolower($rawTerm);
        $cpfDigits = preg_replace('/\D+/', '', $rawTerm) ?: '';

        $query = Inscricao::query()
            ->with('edital:id,titulo,publicado')
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

        $resultados = $query->map(function (Inscricao $inscricao) {
            return [
                'protocolo' => $inscricao->protocolo,
                'nome_completo' => $inscricao->nome_completo,
                'email' => $inscricao->email,
                'cpf' => $inscricao->cpf,
                'status' => $this->statusPublico($inscricao->status),
                'edital' => $inscricao->edital?->titulo ?? '-',
                'submitted_at' => optional($inscricao->submitted_at)->format('d/m/Y H:i'),
                'decided_at' => optional($inscricao->decided_at)->format('d/m/Y H:i'),
            ];
        })->values()->all();

        return redirect()
            ->route('home', ['tab' => 'verificar'])
            ->with([
            'consulta_resultados' => $resultados,
            'consulta_termo' => $rawTerm,
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
            Inscricao::STATUS_HOMOLOGADA => 'Homologada',
            Inscricao::STATUS_INDEFERIDA => 'Indeferida',
            default => 'Em análise',
        };
    }
}
