<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreDocenteRequest;
use App\Http\Requests\AdminUpdateDocenteRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DocenteController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q')->value());
        $perPageRaw = trim((string) $request->string('per_page', '10')->value());
        $perPageOptions = ['10', '20', '50', '100', 'all'];
        if (! in_array($perPageRaw, $perPageOptions, true)) {
            $perPageRaw = '10';
        }

        $query = User::query()
            ->where('role', User::ROLE_DOCENTE)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($nested) use ($q) {
                    $nested
                        ->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%')
                        ->orWhere('telefone', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('name');

        $perPage = $perPageRaw === 'all'
            ? max(1, (clone $query)->count())
            : (int) $perPageRaw;

        $docentes = $query
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.docentes.index', [
            'docentes' => $docentes,
            'q' => $q,
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
        ]);
    }

    public function create(): View
    {
        return view('admin.docentes.form', [
            'docente' => new User(),
            'formAction' => route('admin.docentes.store'),
            'method' => 'POST',
            'isEdit' => false,
        ]);
    }

    public function store(AdminStoreDocenteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefone' => $data['telefone'] ?? null,
            'password' => Str::password(20),
            'role' => User::ROLE_DOCENTE,
            'ativo' => $request->boolean('ativo'),
            'email_verified_at' => now(),
        ]);

        $resetSent = Password::sendResetLink(['email' => $data['email']]) === Password::RESET_LINK_SENT;

        return redirect()
            ->route('admin.docentes.index')
            ->with('status', $resetSent
                ? 'Docente cadastrado com sucesso. Link de redefinição de senha enviado por e-mail.'
                : 'Docente cadastrado com sucesso. Não foi possível enviar o link de redefinição (verifique configuração de e-mail).');
    }

    public function edit(User $docente): View
    {
        abort_unless($docente->role === User::ROLE_DOCENTE, 404);

        return view('admin.docentes.form', [
            'docente' => $docente,
            'formAction' => route('admin.docentes.update', $docente),
            'method' => 'PUT',
            'isEdit' => true,
        ]);
    }

    public function update(AdminUpdateDocenteRequest $request, User $docente): RedirectResponse
    {
        abort_unless($docente->role === User::ROLE_DOCENTE, 404);

        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'telefone' => $data['telefone'] ?? null,
            'ativo' => $request->boolean('ativo'),
        ];

        $docente->update($payload);

        return redirect()
            ->route('admin.docentes.index')
            ->with('status', 'Docente atualizado com sucesso.');
    }

    public function updateStatus(Request $request, User $docente): RedirectResponse
    {
        abort_unless($docente->role === User::ROLE_DOCENTE, 404);

        $docente->update([
            'ativo' => $request->boolean('ativo'),
        ]);

        return redirect()
            ->route('admin.docentes.index')
            ->with('status', 'Status do docente atualizado com sucesso.');
    }

    public function destroy(User $docente): RedirectResponse
    {
        abort_unless($docente->role === User::ROLE_DOCENTE, 404);

        $docente->delete();

        return redirect()
            ->route('admin.docentes.index')
            ->with('status', 'Docente removido com sucesso.');
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
            $email = trim((string) $this->resolveColumn($row, $headers['email_index'], 1));
            $telefone = trim((string) $this->resolveColumn($row, $headers['telefone_index'], 2));

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

            $emailKey = mb_strtolower($email);
            if (in_array($emailKey, $emailsNoArquivo, true)) {
                $falhas[] = [
                    'linha' => $lineNumber,
                    'motivo' => 'email duplicado no arquivo',
                ];
                continue;
            }
            $emailsNoArquivo[] = $emailKey;

            if (User::query()->where('email', $email)->exists()) {
                $falhas[] = [
                    'linha' => $lineNumber,
                    'motivo' => 'email já cadastrado no sistema',
                ];
                continue;
            }

            User::query()->create([
                'name' => $nome,
                'email' => $email,
                'telefone' => $telefone !== '' ? $telefone : null,
                'password' => Str::password(20),
                'role' => User::ROLE_DOCENTE,
                'ativo' => true,
                'email_verified_at' => now(),
            ]);

            Password::sendResetLink(['email' => $email]);
            $importados++;
        }

        return redirect()
            ->route('admin.docentes.index')
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
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($handle, ['nome', 'email', 'telefone'], ';');
            fclose($handle);
        }, 'modelo-importacao-docentes.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
     * @return array{has_header: bool, nome_index: ?int, email_index: ?int, telefone_index: ?int}
     */
    private function detectHeaders(array $firstRow): array
    {
        $normalized = array_map(
            static fn ($v) => mb_strtolower(trim((string) $v)),
            $firstRow
        );

        $nomeIndex = null;
        $emailIndex = null;
        $telefoneIndex = null;

        foreach ($normalized as $index => $col) {
            if (in_array($col, ['nome', 'nome completo', 'nome_completo'], true)) {
                $nomeIndex = $index;
            }
            if (in_array($col, ['email', 'e-mail', 'mail'], true)) {
                $emailIndex = $index;
            }
            if (in_array($col, ['telefone', 'celular', 'fone'], true)) {
                $telefoneIndex = $index;
            }
        }

        $hasHeader = $nomeIndex !== null || $emailIndex !== null || $telefoneIndex !== null;

        return [
            'has_header' => $hasHeader,
            'nome_index' => $nomeIndex,
            'email_index' => $emailIndex,
            'telefone_index' => $telefoneIndex,
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
}
