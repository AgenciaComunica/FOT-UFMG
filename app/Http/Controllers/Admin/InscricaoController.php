<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminHomologarInscricaoRequest;
use App\Http\Requests\AdminIndeferirInscricaoRequest;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricaoController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();
        $search = $request->string('q')->value();
        $date = $request->string('data')->value();
        $editalId = (int) $request->integer('edital_id', 0);
        $perPageRaw = trim((string) $request->string('per_page', '30')->value());
        $perPageOptions = ['30', '50', '100', 'all'];
        if (! in_array($perPageRaw, $perPageOptions, true)) {
            $perPageRaw = '30';
        }

        $query = Inscricao::query()
            ->with('edital')
            ->when($editalId > 0, fn ($query) => $query->where('edital_id', $editalId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($date, fn ($query) => $query->whereDate('submitted_at', $date))
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
            'date' => $date,
            'editalId' => $editalId,
            'perPage' => $perPageRaw,
            'perPageOptions' => $perPageOptions,
            'editais' => Edital::query()->orderByDesc('periodo_inscricao_inicio')->get(['id', 'titulo']),
        ]);
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

        $inscricao->load(['edital.documentosRequeridos', 'documentos', 'user', 'decidedByUser']);

        return view('admin.inscricoes.show', [
            'inscricao' => $inscricao,
            'podeHomologar' => $inscricao->status === Inscricao::STATUS_RECEBIDA
                && $inscricao->possuiDocumentosObrigatorios(),
        ]);
    }

    public function homologar(AdminHomologarInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        $flashPassword = null;
        $flashMessage = null;

        DB::transaction(function () use ($request, $inscricao, &$flashPassword, &$flashMessage): void {
            $inscricao = Inscricao::query()
                ->with(['edital.documentosRequeridos', 'documentos'])
                ->lockForUpdate()
                ->findOrFail($inscricao->id);

            if ($inscricao->status !== Inscricao::STATUS_RECEBIDA) {
                throw ValidationException::withMessages([
                    'status' => 'Apenas inscrições recebidas podem ser homologadas.',
                ]);
            }

            if (! $inscricao->possuiDocumentosObrigatorios()) {
                throw ValidationException::withMessages([
                    'documentos' => 'Não é possível homologar com documentos obrigatórios faltando.',
                ]);
            }

            $user = User::query()->where('email', $inscricao->email)->first();

            if ($user && $user->role !== User::ROLE_ALUNO) {
                throw ValidationException::withMessages([
                    'email' => 'Já existe usuário com esse e-mail e role diferente de aluno.',
                ]);
            }

            if (! $user) {
                $user = User::create([
                    'name' => $inscricao->nome_completo,
                    'email' => $inscricao->email,
                    'password' => Str::password(20),
                    'role' => User::ROLE_ALUNO,
                    'email_verified_at' => now(),
                ]);
            }

            $inscricao->update([
                'status' => Inscricao::STATUS_HOMOLOGADA,
                'decided_at' => now(),
                'decided_by' => $request->user()->id,
                'indeferimento_motivo' => null,
                'user_id' => $user->id,
            ]);

            $canUseResetFlow = config('mail.default') !== 'log';
            if ($canUseResetFlow && Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT) {
                $flashMessage = 'Inscrição homologada e link para definir senha enviado ao aluno.';

                return;
            }

            $flashPassword = Str::password(14);
            $user->forceFill([
                'password' => $flashPassword,
            ])->save();

            $flashMessage = 'Inscrição homologada. E-mail não configurado: use a senha temporária para repasse manual.';
        });

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', $flashMessage)
            ->with('senha_temporaria', $flashPassword);
    }

    public function indeferir(AdminIndeferirInscricaoRequest $request, Inscricao $inscricao): RedirectResponse
    {
        if ($inscricao->status !== Inscricao::STATUS_RECEBIDA) {
            throw ValidationException::withMessages([
                'status' => 'Apenas inscrições recebidas podem ser indeferidas.',
            ]);
        }

        $inscricao->update([
            'status' => Inscricao::STATUS_INDEFERIDA,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
            'indeferimento_motivo' => $request->validated('indeferimento_motivo'),
        ]);

        return redirect()
            ->route('admin.inscricoes.show', $inscricao)
            ->with('status', 'Inscrição indeferida com sucesso.');
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
}
