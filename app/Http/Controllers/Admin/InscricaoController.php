<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminHomologarInscricaoRequest;
use App\Http\Requests\AdminIndeferirInscricaoRequest;
use App\Http\Requests\AdminUpdateInscricaoRequest;
use App\Mail\InscricaoRecebidaMail;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoAvaliacao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use App\Services\InscricaoPreClassificacaoService;
use App\Services\InscricaoWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricaoController extends Controller
{
    public function __construct(
        private readonly InscricaoPreClassificacaoService $preClassificacaoService,
        private readonly InscricaoWorkflowService $workflowService,
    ) {}

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
            'ultimaEdicao',
            'edicoes' => fn ($q) => $q->latest('edited_at'),
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
                    'avaliacao_subjetiva' => $avaliacao?->avaliacao_subjetiva,
                    'comentario' => $avaliacao?->comentario,
                    'avaliado_at' => $avaliacao?->avaliado_at,
                    'ultima_avaliacao_at' => $avaliacao && $avaliacao->nota !== null ? $avaliacao->updated_at : null,
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
        ]);
    }

    public function update(AdminUpdateInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        $data = $request->validated();

        $emailAlterado = mb_strtolower($inscricao->email) !== mb_strtolower($data['email']);
        $cpfAlterado = preg_replace('/\D+/', '', (string) $inscricao->cpf) !== preg_replace('/\D+/', '', (string) $data['cpf']);
        $emailConfirmadoManual = $request->boolean('email_confirmado');

        $emailVerifiedAt = $emailConfirmadoManual
            ? now()
            : ($emailAlterado ? null : $inscricao->email_verified_at);
        $emailVerificationToken = $emailConfirmadoManual
            ? null
            : ($emailAlterado ? hash('sha256', Str::uuid().'|'.$data['email']) : $inscricao->email_verification_token);
        $verificationSentAt = $emailConfirmadoManual
            ? now()
            : ($emailAlterado ? null : $inscricao->verification_sent_at);

        $inscricao->update([
            'nome_completo' => $data['nome_completo'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'telefone' => $data['telefone'] ?? null,
            'email_verified_at' => $emailVerifiedAt,
            'email_verification_token' => $emailVerificationToken,
            'verification_sent_at' => $verificationSentAt,
        ]);

        if ($cpfAlterado || $emailAlterado) {
            $inscricao->forceFill([
                'status' => $inscricao->status === Inscricao::STATUS_HOMOLOGADA ? Inscricao::STATUS_RECEBIDA : $inscricao->status,
            ])->save();
        }

        if ($emailAlterado && ! $emailConfirmadoManual) {
            $this->enviarVerificacaoInscricao($inscricao->fresh(['edital']));
        }

        return redirect()
            ->route('admin.inscricoes.show', ['inscricao' => $inscricao, 'tab' => 'dados'])
            ->with('status', 'Dados da inscrição atualizados com sucesso.');
    }

    public function destroy(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);

        $this->deleteInscricaoWithFiles($inscricao);

        return back()->with('status', 'Inscrição excluída com sucesso.');
    }

    public function updateDocumento(Request $request, Inscricao $inscricao, InscricaoDocumento $doc): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        $this->authorize('view', $doc);
        abort_unless($doc->inscricao_id === $inscricao->id, 404);

        $maxKb = (int) config('inscricoes.max_pdf_kb', 10_240);
        $validated = $request->validate([
            'arquivo' => ['required', 'file', 'max:'.$maxKb],
        ], [
            'arquivo.required' => 'Selecione um arquivo para substituição.',
            'arquivo.file' => 'Arquivo inválido.',
            'arquivo.max' => 'O arquivo excede o tamanho máximo permitido.',
        ]);

        $inscricao->loadMissing('edital.documentosRequeridos');

        $file = $validated['arquivo'];
        $extensao = strtolower((string) $file->getClientOriginalExtension());
        if ($extensao === 'jpeg') {
            $extensao = 'jpg';
        }

        $formatosAceitos = $this->formatosAceitosDocumento($inscricao, $doc->tipo);
        if (! in_array($extensao, $formatosAceitos, true)) {
            throw ValidationException::withMessages([
                'arquivo' => 'Formato inválido. Permitidos: '.strtoupper(implode(', ', $formatosAceitos)).'.',
            ]);
        }

        $this->assertMimeDocumento($file->getMimeType(), $extensao);

        if (filled($doc->arquivo_path) && Storage::disk('local')->exists($doc->arquivo_path)) {
            Storage::disk('local')->delete($doc->arquivo_path);
        }

        $safeTipo = Str::slug($doc->tipo, '_');
        $fileName = 'doc_'.$doc->id.'_'.$safeTipo.'.'.$extensao;
        $directory = 'inscricoes/'.$inscricao->id;
        Storage::disk('local')->putFileAs($directory, $file, $fileName);

        $doc->update([
            'arquivo_path' => $directory.'/'.$fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_at' => now(),
        ]);

        return redirect()
            ->route('admin.inscricoes.show', ['inscricao' => $inscricao, 'tab' => 'documentos'])
            ->with('status', 'Documento substituído com sucesso.');
    }

    public function storeDocumento(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);

        $maxKb = (int) config('inscricoes.max_pdf_kb', 10_240);
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'max:120'],
            'arquivo' => ['required', 'file', 'max:'.$maxKb],
        ], [
            'tipo.required' => 'Selecione o tipo do documento.',
            'arquivo.required' => 'Selecione um arquivo para envio.',
            'arquivo.file' => 'Arquivo inválido.',
            'arquivo.max' => 'O arquivo excede o tamanho máximo permitido.',
        ]);

        $inscricao->loadMissing('edital.documentosRequeridos', 'documentos');

        $tipo = trim((string) $validated['tipo']);
        $file = $validated['arquivo'];
        $extensao = strtolower((string) $file->getClientOriginalExtension());
        if ($extensao === 'jpeg') {
            $extensao = 'jpg';
        }

        $formatosAceitos = $this->formatosAceitosDocumento($inscricao, $tipo);
        if (! in_array($extensao, $formatosAceitos, true)) {
            throw ValidationException::withMessages([
                'arquivo' => 'Formato inválido. Permitidos: '.strtoupper(implode(', ', $formatosAceitos)).'.',
            ]);
        }

        $this->assertMimeDocumento($file->getMimeType(), $extensao);

        $doc = InscricaoDocumento::create([
            'inscricao_id' => $inscricao->id,
            'tipo' => $tipo,
            'arquivo_path' => '',
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_at' => now(),
        ]);

        $safeTipo = Str::slug($tipo, '_');
        $fileName = 'doc_'.$doc->id.'_'.$safeTipo.'.'.$extensao;
        $directory = 'inscricoes/'.$inscricao->id;
        Storage::disk('local')->putFileAs($directory, $file, $fileName);

        $doc->update([
            'arquivo_path' => $directory.'/'.$fileName,
        ]);

        return redirect()
            ->route('admin.inscricoes.show', ['inscricao' => $inscricao, 'tab' => 'documentos'])
            ->with('status', 'Documento enviado com sucesso.');
    }

    public function destroyDocumento(Inscricao $inscricao, InscricaoDocumento $doc): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        $this->authorize('view', $doc);
        abort_unless($doc->inscricao_id === $inscricao->id, 404);

        if (filled($doc->arquivo_path) && Storage::disk('local')->exists($doc->arquivo_path)) {
            Storage::disk('local')->delete($doc->arquivo_path);
        }

        $doc->delete();

        return redirect()
            ->route('admin.inscricoes.show', ['inscricao' => $inscricao, 'tab' => 'documentos'])
            ->with('status', 'Documento removido com sucesso.');
    }

    public function salvarAvaliacao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        if (! $inscricao->isEmailVerified()) {
            throw ValidationException::withMessages([
                'avaliacao' => 'A inscrição só pode ser avaliada após verificação do e-mail.',
            ]);
        }

        $data = $request->validate([
            'docente_id' => ['required', 'integer', 'exists:users,id'],
            'nota' => ['required', 'numeric', 'min:0', 'max:10'],
            'avaliacao_subjetiva' => ['required', 'in:HOMOLOGAR,INDEFERIR,ABSTER'],
            'comentario' => ['nullable', 'string', 'max:2000'],
            'confirm_code_expected' => ['required', 'digits:2'],
            'confirm_code_input' => ['required', 'digits:2'],
        ], [
            'nota.required' => 'Informe a nota da avaliação.',
            'nota.numeric' => 'A nota deve ser numérica.',
            'nota.min' => 'A nota mínima é 0.',
            'nota.max' => 'A nota máxima é 10.',
            'avaliacao_subjetiva.required' => 'Selecione a avaliação subjetiva.',
            'avaliacao_subjetiva.in' => 'Avaliação subjetiva inválida.',
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
                'avaliacao_subjetiva' => $data['avaliacao_subjetiva'],
                'comentario' => filled($data['comentario'] ?? null) ? trim((string) $data['comentario']) : null,
                'avaliado_at' => now(),
            ]
        );

        $this->preClassificacaoService->recalcular($inscricao->edital()->firstOrFail());

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Avaliação atualizada com sucesso.');
    }

    public function limparAvaliacao(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        if (! $inscricao->isEmailVerified()) {
            throw ValidationException::withMessages([
                'avaliacao' => 'A inscrição só pode ser avaliada após verificação do e-mail.',
            ]);
        }

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

        $this->preClassificacaoService->recalcular($inscricao->edital()->firstOrFail());

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

    private function formatosAceitosDocumento(Inscricao $inscricao, string $tipo): array
    {
        $requerido = $inscricao->edital?->documentosRequeridos
            ->first(fn ($doc) => $doc->tipo === $tipo);

        $formatos = $requerido?->formatos_aceitos ?? [];

        if (empty($formatos)) {
            return ['pdf', 'docx', 'jpg', 'png'];
        }

        return $formatos;
    }

    private function assertMimeDocumento(?string $mime, string $extensao): void
    {
        $mime = strtolower((string) $mime);
        $mimeValidoPorExtensao = [
            'pdf' => ['application/pdf', 'application/x-pdf'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
            'jpg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
        ];

        if (
            isset($mimeValidoPorExtensao[$extensao]) &&
            ! in_array($mime, $mimeValidoPorExtensao[$extensao], true)
        ) {
            throw ValidationException::withMessages([
                'arquivo' => 'MIME inválido para o formato enviado.',
            ]);
        }
    }

    public function homologar(AdminHomologarInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        $temporaryPassword = DB::transaction(function () use ($request, $inscricao) {
            $inscricao = Inscricao::query()
                ->with(['edital.documentosRequeridos', 'documentos'])
                ->lockForUpdate()
                ->findOrFail($inscricao->id);

            return $this->workflowService->applyStatus(
                $inscricao,
                Inscricao::STATUS_HOMOLOGADA,
                $request->user()->id,
            );
        });

        $redirect = redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Inscrição homologada com sucesso. Agora ela está apta para classificação.');

        if ($temporaryPassword) {
            $redirect->with('senha_temporaria', $temporaryPassword);
        }

        return $redirect;
    }

    public function updateStatus(Request $request, Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);

        $data = $request->validate([
            'status' => ['required', 'in:RECEBIDA,HOMOLOGADA,INDEFERIDA,PRE_APROVADA,PRE_INDEFERIDA'],
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

        $temporaryPassword = DB::transaction(function () use ($request, $inscricao, $data) {
            $inscricao = Inscricao::query()
                ->with(['edital.documentosRequeridos', 'documentos'])
                ->lockForUpdate()
                ->findOrFail($inscricao->id);

            return $this->workflowService->applyStatus(
                $inscricao,
                $data['status'],
                $request->user()->id,
                $data['indeferimento_motivo'] ?? null
            );
        });

        $redirect = redirect()
            ->route('admin.inscricoes.show', ['inscricao' => $inscricao, 'tab' => 'dados'])
            ->with('status', 'Status da inscrição atualizado com sucesso.');

        if ($temporaryPassword) {
            $redirect->with('senha_temporaria', $temporaryPassword);
        }

        return $redirect;
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:RECEBIDA,HOMOLOGADA,INDEFERIDA,PRE_APROVADA,PRE_INDEFERIDA'],
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
        $temporaryPasswords = [];
        $query->chunkById(100, function ($chunk) use (&$updated, $data, $request): void {
            foreach ($chunk as $inscricao) {
                DB::transaction(function () use ($inscricao, $data, $request, &$updated, &$temporaryPasswords): void {
                    $locked = Inscricao::query()->lockForUpdate()->findOrFail($inscricao->id);
                    $temporaryPassword = $this->workflowService->applyStatus(
                        $locked,
                        $data['status'],
                        $request->user()->id,
                        $data['indeferimento_motivo'] ?? null
                    );
                    if ($temporaryPassword) {
                        $temporaryPasswords[] = $locked->email.': '.$temporaryPassword;
                    }
                    $updated++;
                });
            }
        });

        $redirect = back()->with('status', "Ação em lote aplicada em {$updated} inscrição(ões).");
        if ($temporaryPasswords !== []) {
            $redirect->with('senha_temporaria', implode(' | ', $temporaryPasswords));
        }

        return $redirect;
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'exists:inscricoes,id'],
        ]);

        $ids = collect($data['selected_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_ids' => 'Selecione ao menos uma inscrição para excluir em lote.',
            ]);
        }

        $deleted = 0;
        Inscricao::query()
            ->whereIn('id', $ids)
            ->chunkById(100, function ($chunk) use (&$deleted): void {
                foreach ($chunk as $inscricao) {
                    $this->deleteInscricaoWithFiles($inscricao);
                    $deleted++;
                }
            });

        return back()->with('status', "Inscrição(ões) excluída(s): {$deleted}.");
    }

    public function bulkEnviarLembreteVerificacao(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'exists:inscricoes,id'],
        ]);

        $ids = collect($validated['selected_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_ids' => 'Selecione ao menos uma inscrição para enviar o lembrete de verificação.',
            ]);
        }

        $inscricoes = Inscricao::query()
            ->whereIn('id', $ids)
            ->whereNull('email_verified_at')
            ->whereNotNull('email')
            ->with('edital')
            ->get()
            ->filter(fn (Inscricao $inscricao) => $this->canReceiveVerificationReminder($inscricao))
            ->unique('id')
            ->values();

        if ($inscricoes->isEmpty()) {
            return back()->with('status', 'Nenhuma inscrição elegível para lembrete de verificação de e-mail.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($inscricoes as $inscricao) {
            $inscricao->forceFill([
                'email_verification_token' => hash('sha256', Str::uuid().'|'.$inscricao->email),
                'verification_sent_at' => now(),
            ])->save();

            if ($this->enviarVerificacaoInscricao($inscricao->fresh('edital'))) {
                $sent++;

                continue;
            }

            $failed++;
        }

        $message = "{$sent} lembrete(s) de verificação enviados com sucesso.";
        if ($failed > 0) {
            $message .= " {$failed} envio(s) não puderam ser concluídos.";
        }

        return back()->with('status', $message);
    }

    public function enviarLembreteVerificacao(Inscricao $inscricao): RedirectResponse
    {
        $this->authorize('view', $inscricao);
        $inscricao->loadMissing('edital');

        if (! $this->canReceiveVerificationReminder($inscricao)) {
            return back()->with('status', 'Esta inscrição não está elegível para lembrete de verificação de e-mail.');
        }

        $inscricao->forceFill([
            'email_verification_token' => hash('sha256', Str::uuid().'|'.$inscricao->email),
            'verification_sent_at' => now(),
        ])->save();

        if (! $this->enviarVerificacaoInscricao($inscricao->fresh('edital'))) {
            return back()->with('status', 'Não foi possível enviar o lembrete de verificação agora.');
        }

        return back()->with('status', 'Lembrete de verificação enviado com sucesso.');
    }

    public function indeferir(AdminIndeferirInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        DB::transaction(function () use ($request, $inscricao): void {
            $locked = Inscricao::query()->lockForUpdate()->findOrFail($inscricao->id);
            $this->workflowService->applyStatus(
                $locked,
                Inscricao::STATUS_INDEFERIDA,
                $request->user()->id,
                $request->validated('indeferimento_motivo'),
            );
        });

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Inscrição não homologada com sucesso.');
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

    private function statusPublico(string $status): string
    {
        return $this->workflowService->statusPublico($status);
    }

    private function canReceiveVerificationReminder(Inscricao $inscricao): bool
    {
        $inscricao->loadMissing('edital');

        return ! $inscricao->isEmailVerified()
            && filled($inscricao->email)
            && $inscricao->edital
            && ! $inscricao->edital->isArquivado();
    }

    private function enviarVerificacaoInscricao(Inscricao $inscricao): bool
    {
        if (! filled($inscricao->email) || ! filled($inscricao->email_verification_token)) {
            return false;
        }

        $verificationUrl = route('public.inscricao.email.verificar', [
            'inscricao' => $inscricao,
            'token' => $inscricao->email_verification_token,
        ]);
        $statusUrl = route('home', ['tab' => 'verificar']);

        try {
            Mail::to($inscricao->email)->send(
                new InscricaoRecebidaMail($inscricao, $verificationUrl, $statusUrl)
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de verificação da inscrição (admin).', [
                'inscricao_id' => $inscricao->id,
                'protocolo' => $inscricao->protocolo,
                'email' => $inscricao->email,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function deleteInscricaoWithFiles(Inscricao $inscricao): void
    {
        $inscricao->loadMissing('documentos');

        foreach ($inscricao->documentos as $doc) {
            if (filled($doc->arquivo_path) && Storage::disk('local')->exists($doc->arquivo_path)) {
                Storage::disk('local')->delete($doc->arquivo_path);
            }
        }

        $directory = 'inscricoes/'.$inscricao->id;
        if (Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->deleteDirectory($directory);
        }

        $inscricao->delete();
    }
}
