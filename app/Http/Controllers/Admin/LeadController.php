<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LeadNovidadeEditalMail;
use App\Models\Edital;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q')->value());
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

        $query = Lead::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested
                        ->where('nome', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->when($dateStart && $dateEnd, fn ($query) => $query->whereBetween('updated_at', [$dateStart, $dateEnd]))
            ->latest('updated_at');

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        $leads = $query->paginate($perPage)->withQueryString();

        return view('admin.leads.index', [
            'leads' => $leads,
            'q' => $q,
            'dateStart' => $dateStart?->format('Y-m-d'),
            'dateEnd' => $dateEnd?->format('Y-m-d'),
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
            'editais' => Edital::query()->orderByDesc('periodo_inscricao_inicio')->get(['id', 'titulo', 'periodo_inscricao_inicio', 'periodo_inscricao_fim']),
        ]);
    }

    public function create(): View
    {
        return view('admin.leads.form', [
            'lead' => new Lead(),
            'formAction' => route('admin.leads.store'),
            'method' => 'POST',
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateLead($request);

        Lead::query()->create($data);

        return redirect()
            ->route('admin.leads.index')
            ->with('status', 'Lead cadastrado com sucesso.');
    }

    public function edit(Lead $lead): View
    {
        return view('admin.leads.form', [
            'lead' => $lead,
            'formAction' => route('admin.leads.update', $lead),
            'method' => 'PUT',
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $this->validateLead($request, $lead);

        $lead->update($data);

        return redirect()
            ->route('admin.leads.index')
            ->with('status', 'Lead atualizado com sucesso.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('admin.leads.index')
            ->with('status', 'Lead removido com sucesso.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ], [
            'arquivo.required' => 'Selecione um arquivo CSV ou Excel para importar.',
            'arquivo.mimes' => 'Formato inválido. Use CSV ou XLSX.',
        ]);

        /** @var UploadedFile $arquivo */
        $arquivo = $validated['arquivo'];
        $extension = strtolower((string) $arquivo->getClientOriginalExtension());

        if ($extension === 'xls') {
            throw ValidationException::withMessages([
                'arquivo' => 'Arquivo .xls não suportado no momento. Salve como .xlsx ou .csv.',
            ]);
        }

        $rows = $extension === 'xlsx'
            ? $this->parseXlsxRows($arquivo)
            : $this->parseCsvRows($arquivo);

        if (count($rows) === 0) {
            throw ValidationException::withMessages([
                'arquivo' => 'Nenhuma linha encontrada para importação.',
            ]);
        }

        $headers = $this->detectHeaders($rows[0]);
        $startIndex = $headers['has_header'] ? 1 : 0;
        $importados = 0;
        $falhas = [];
        $emailsNoArquivo = [];

        for ($i = $startIndex; $i < count($rows); $i++) {
            $lineNumber = $i + 1;
            $row = $rows[$i];

            $nome = trim((string) $this->resolveColumn($row, $headers['nome_index'], 0));
            $email = mb_strtolower(trim((string) $this->resolveColumn($row, $headers['email_index'], 1)));

            $faltantes = [];
            if ($nome === '') {
                $faltantes[] = 'nome';
            }
            if ($email === '') {
                $faltantes[] = 'email';
            }

            if ($faltantes !== []) {
                $falhas[] = [
                    'linha' => $lineNumber,
                    'motivo' => 'faltou '.implode(' e ', $faltantes),
                ];
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $falhas[] = [
                    'linha' => $lineNumber,
                    'motivo' => 'email inválido',
                ];
                continue;
            }

            if (in_array($email, $emailsNoArquivo, true)) {
                $falhas[] = [
                    'linha' => $lineNumber,
                    'motivo' => 'email duplicado no arquivo',
                ];
                continue;
            }
            $emailsNoArquivo[] = $email;

            $lead = Lead::query()->firstWhere('email', $email);
            if ($lead) {
                $lead->forceFill([
                    'nome' => $nome,
                    'updated_at' => now(),
                ])->save();
            } else {
                Lead::query()->create([
                    'nome' => $nome,
                    'email' => $email,
                ]);
            }

            $importados++;
        }

        return redirect()
            ->route('admin.leads.index')
            ->with('import_result', [
                'importados' => $importados,
                'falhas_total' => count($falhas),
                'falhas' => $falhas,
            ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['nome', 'email'], ';');
            fclose($handle);
        }, 'modelo-importacao-leads.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sendManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'selected_ids' => ['required', 'array'],
            'selected_ids.*' => ['integer', 'exists:leads,id'],
            'edital_id' => ['required', 'integer', 'exists:editais,id'],
            'tipo_aviso' => ['required', 'in:aberto,encerrando'],
        ], [
            'selected_ids.required' => 'Selecione ao menos um lead para disparo.',
            'edital_id.required' => 'Selecione o edital do aviso.',
            'tipo_aviso.required' => 'Selecione o tipo de aviso.',
        ]);

        $leadIds = collect($data['selected_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        if ($leadIds->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_ids' => 'Selecione ao menos um lead para disparo.',
            ]);
        }

        $edital = Edital::query()->findOrFail((int) $data['edital_id']);
        $tipoAviso = (string) $data['tipo_aviso'];
        $portalUrl = route('home', ['tab' => 'editais']);
        $editalUrl = route('public.editais.download', $edital);

        $sent = 0;
        Lead::query()
            ->whereIn('id', $leadIds)
            ->chunkById(100, function ($chunk) use (&$sent, $edital, $tipoAviso, $portalUrl, $editalUrl): void {
                foreach ($chunk as $lead) {
                    try {
                        Mail::to($lead->email)->send(new LeadNovidadeEditalMail(
                            $lead->nome,
                            $edital,
                            $tipoAviso,
                            $portalUrl,
                            $editalUrl,
                        ));

                        $lead->forceFill([
                            'last_notified_at' => now(),
                        ])->save();
                        $sent++;
                    } catch (\Throwable) {
                    }
                }
            });

        return redirect()
            ->route('admin.leads.index', $request->only(['q', 'data_inicio', 'data_fim', 'per_page']))
            ->with('status', "Disparo realizado para {$sent} lead(s).");
    }

    public function exportSelected(Request $request)
    {
        $data = $request->validate([
            'selected_ids' => ['required', 'array'],
            'selected_ids.*' => ['integer', 'exists:leads,id'],
            'formato' => ['required', 'in:csv,xls'],
        ], [
            'selected_ids.required' => 'Selecione ao menos um lead para exportação.',
            'formato.required' => 'Selecione o formato de exportação.',
            'formato.in' => 'Formato de exportação inválido.',
        ]);

        $leadIds = collect($data['selected_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        if ($leadIds->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_ids' => 'Selecione ao menos um lead para exportação.',
            ]);
        }

        $leads = Lead::query()
            ->whereIn('id', $leadIds)
            ->orderBy('nome')
            ->get(['id', 'nome', 'email', 'updated_at', 'last_notified_at']);

        $filenameBase = 'leads-selecionados-'.now()->format('Ymd-His');

        if ($data['formato'] === 'csv') {
            return response()->streamDownload(function () use ($leads): void {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($handle, ['ID', 'Nome', 'E-mail', 'Último cadastro', 'Último disparo'], ';');

                foreach ($leads as $lead) {
                    fputcsv($handle, [
                        $lead->id,
                        $lead->nome,
                        $lead->email,
                        optional($lead->updated_at)->format('d/m/Y H:i'),
                        optional($lead->last_notified_at)->format('d/m/Y H:i'),
                    ], ';');
                }

                fclose($handle);
            }, $filenameBase.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response()
            ->view('admin.leads.export_xls', [
                'leads' => $leads,
            ])
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filenameBase.'.xls"');
    }

    private function validateLead(Request $request, ?Lead $lead = null): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('leads', 'email')->ignore($lead?->id)],
        ], [
            'nome.required' => 'Informe o nome do lead.',
            'email.required' => 'Informe o e-mail do lead.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado como lead.',
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsvRows(UploadedFile $arquivo): array
    {
        $path = $arquivo->getRealPath();
        if (! $path) {
            return [];
        }

        $rows = [];
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [];
        }

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (count($data) <= 1) {
                $data = str_getcsv((string) ($data[0] ?? ''), ',');
            }

            $row = array_map(static fn ($value) => trim((string) $value), $data);
            if (implode('', $row) === '') {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseXlsxRows(UploadedFile $arquivo): array
    {
        $path = $arquivo->getRealPath();
        if (! $path) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (! $sheetXml) {
            $zip->close();
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $shared = simplexml_load_string($sharedXml);
            if ($shared !== false) {
                foreach ($shared->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = trim($text);
                }
            }
        }

        $sheet = simplexml_load_string($sheetXml);
        $zip->close();
        if ($sheet === false || ! isset($sheet->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $colIndex = $this->columnLettersToIndex(preg_replace('/\d+/', '', $ref));
                $type = (string) ($cell['t'] ?? '');

                $value = '';
                if ($type === 's') {
                    $sharedIndex = (int) ($cell->v ?? 0);
                    $value = (string) ($sharedStrings[$sharedIndex] ?? '');
                } else {
                    $value = trim((string) ($cell->v ?? ''));
                }

                $row[$colIndex] = trim($value);
            }

            if ($row === []) {
                continue;
            }

            ksort($row);
            $max = max(array_keys($row));
            $normalized = [];
            for ($i = 0; $i <= $max; $i++) {
                $normalized[] = (string) ($row[$i] ?? '');
            }

            if (implode('', $normalized) === '') {
                continue;
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $firstRow
     * @return array{has_header: bool, nome_index: ?int, email_index: ?int}
     */
    private function detectHeaders(array $firstRow): array
    {
        $normalized = array_map(
            static fn ($v) => mb_strtolower(trim((string) $v)),
            $firstRow
        );

        $nomeIndex = null;
        $emailIndex = null;

        foreach ($normalized as $index => $col) {
            if (in_array($col, ['nome', 'nome completo', 'nome_completo'], true)) {
                $nomeIndex = $index;
            }
            if (in_array($col, ['email', 'e-mail', 'mail'], true)) {
                $emailIndex = $index;
            }
        }

        $hasHeader = $nomeIndex !== null || $emailIndex !== null;

        return [
            'has_header' => $hasHeader,
            'nome_index' => $nomeIndex,
            'email_index' => $emailIndex,
        ];
    }

    /**
     * @param  array<int, string>  $row
     */
    private function resolveColumn(array $row, ?int $headerIndex, int $fallbackIndex): string
    {
        if ($headerIndex !== null) {
            return (string) ($row[$headerIndex] ?? '');
        }

        return (string) ($row[$fallbackIndex] ?? '');
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        if ($letters === '') {
            return 0;
        }

        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
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
}
