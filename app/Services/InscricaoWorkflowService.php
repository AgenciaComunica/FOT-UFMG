<?php

namespace App\Services;

use App\Mail\InscricaoResultadoMail;
use App\Models\Inscricao;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InscricaoWorkflowService
{
    public function applyStatus(Inscricao $inscricao, string $status, int $userId, ?string $indeferimentoMotivo = null): ?string
    {
        if ($status === Inscricao::STATUS_HOMOLOGADA) {
            return $this->homologar($inscricao);
        }

        if ($status === Inscricao::STATUS_PRE_APROVADA) {
            return $this->classificar($inscricao, $userId);
        }

        if ($status === Inscricao::STATUS_PRE_INDEFERIDA) {
            return $this->marcarExcedente($inscricao, $userId);
        }

        if ($status === Inscricao::STATUS_INDEFERIDA) {
            return $this->naoHomologar($inscricao, $userId, $indeferimentoMotivo);
        }

        return $this->voltarHomologacao($inscricao, $userId);
    }

    public function statusPublico(string $status): string
    {
        return match ($status) {
            Inscricao::STATUS_PRE_APROVADA => 'Classificada',
            Inscricao::STATUS_PRE_INDEFERIDA => 'Excedente',
            Inscricao::STATUS_HOMOLOGADA => 'Homologada',
            Inscricao::STATUS_INDEFERIDA => 'Não homologada',
            default => 'Em homologação',
        };
    }

    private function homologar(Inscricao $inscricao): ?string
    {
        if (! in_array($inscricao->status, [
            Inscricao::STATUS_RECEBIDA,
            Inscricao::STATUS_INDEFERIDA,
            Inscricao::STATUS_PRE_APROVADA,
            Inscricao::STATUS_PRE_INDEFERIDA,
            Inscricao::STATUS_HOMOLOGADA,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'A inscrição não pode voltar para homologação a partir do status atual.',
            ]);
        }

        if (! $inscricao->possuiDocumentosObrigatorios()) {
            throw ValidationException::withMessages([
                'documentos' => 'Não é possível homologar com documentos obrigatórios faltando.',
            ]);
        }

        if (! $inscricao->isEmailVerified()) {
            throw ValidationException::withMessages([
                'email' => 'Não é possível homologar sem e-mail verificado da inscrição.',
            ]);
        }

        $this->desativarAlunoSeNecessario($inscricao);

        $inscricao->forceFill([
            'status' => Inscricao::STATUS_HOMOLOGADA,
            'decided_at' => null,
            'decided_by' => null,
            'indeferimento_motivo' => null,
            'user_id' => null,
        ])->save();

        $this->enviarResultadoInscricao($inscricao);

        return null;
    }

    private function classificar(Inscricao $inscricao, int $userId): ?string
    {
        if (! $inscricao->estaHomologada()) {
            throw ValidationException::withMessages([
                'status' => 'A inscrição precisa estar homologada para ser classificada.',
            ]);
        }

        $this->desativarAlunoSeNecessario($inscricao);

        $inscricao->forceFill([
            'status' => Inscricao::STATUS_PRE_APROVADA,
            'decided_at' => now(),
            'decided_by' => $userId,
            'indeferimento_motivo' => null,
            'user_id' => null,
        ])->save();

        $this->enviarResultadoInscricao($inscricao);
        
        return null;
    }

    private function marcarExcedente(Inscricao $inscricao, int $userId): ?string
    {
        if (! $inscricao->estaHomologada()) {
            throw ValidationException::withMessages([
                'status' => 'A inscrição precisa estar homologada para ser marcada como excedente.',
            ]);
        }

        $this->desativarAlunoSeNecessario($inscricao);

        $inscricao->forceFill([
            'status' => Inscricao::STATUS_PRE_INDEFERIDA,
            'decided_at' => now(),
            'decided_by' => $userId,
            'indeferimento_motivo' => null,
            'user_id' => null,
        ])->save();

        $this->enviarResultadoInscricao($inscricao);

        return null;
    }

    private function naoHomologar(Inscricao $inscricao, int $userId, ?string $indeferimentoMotivo): ?string
    {
        if (! filled($indeferimentoMotivo)) {
            throw ValidationException::withMessages([
                'indeferimento_motivo' => 'O motivo é obrigatório para definir como não homologada.',
            ]);
        }

        $this->desativarAlunoSeNecessario($inscricao);

        $inscricao->forceFill([
            'status' => Inscricao::STATUS_INDEFERIDA,
            'decided_at' => now(),
            'decided_by' => $userId,
            'indeferimento_motivo' => trim((string) $indeferimentoMotivo),
            'user_id' => null,
        ])->save();

        $this->enviarResultadoInscricao($inscricao);

        return null;
    }

    private function voltarHomologacao(Inscricao $inscricao, int $userId): ?string
    {
        if (! in_array($inscricao->status, [
            Inscricao::STATUS_HOMOLOGADA,
            Inscricao::STATUS_PRE_APROVADA,
            Inscricao::STATUS_PRE_INDEFERIDA,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Apenas inscrições homologadas, classificadas ou excedentes podem voltar para homologação.',
            ]);
        }

        return $this->homologar($inscricao);
    }

    private function enviarResultadoInscricao(Inscricao $inscricao): void
    {
        if (! filled($inscricao->email)) {
            return;
        }

        $editUrl = null;
        if ($inscricao->permiteEdicaoPublica() && $inscricao->edital?->isAberto()) {
            $rawToken = Str::random(64);
            $inscricao->forceFill([
                'edit_link_token' => hash('sha256', $rawToken),
                'edit_link_sent_at' => now(),
                'edit_link_expires_at' => now()->addHours(max(1, (int) config('inscricoes.edit_link_hours', 24))),
                'edit_link_used_at' => null,
            ])->save();

            $editUrl = route('public.inscricoes.editar', [
                'inscricao' => $inscricao->id,
                'token' => $rawToken,
            ]);
        }

        try {
            Mail::to($inscricao->email)->send(
                new InscricaoResultadoMail(
                    $inscricao->fresh(['edital']),
                    $this->statusPublico($inscricao->status),
                    route('home', ['tab' => 'verificar']),
                    $editUrl,
                )
            );
        } catch (\Throwable) {
        }

        if (Schema::hasColumn('inscricoes', 'resultado_email_sent_at')) {
            $inscricao->forceFill([
                'resultado_email_sent_at' => now(),
            ])->save();
        }
    }

    private function desativarAlunoSeNecessario(Inscricao $inscricao): void
    {
        if (! $inscricao->user_id) {
            return;
        }

        $user = User::query()->find($inscricao->user_id);
        if (! $user || $user->role !== User::ROLE_ALUNO) {
            return;
        }

        $user->forceFill(['ativo' => false])->save();
    }
}
