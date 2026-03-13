<?php

namespace App\Services;

use App\Models\Edital;
use App\Models\Inscricao;

class InscricaoPreClassificacaoService
{
    public function recalcular(Edital $edital): void
    {
        $edital->loadMissing('docentesBanca:id');
        $bancaIds = $edital->docentesBanca->pluck('id')->map(fn ($id) => (int) $id)->all();
        $totalBanca = count($bancaIds);

        $inscricoes = Inscricao::query()
            ->where('edital_id', $edital->id)
            ->whereIn('status', [
                Inscricao::STATUS_HOMOLOGADA,
                Inscricao::STATUS_PRE_APROVADA,
                Inscricao::STATUS_PRE_INDEFERIDA,
            ])
            ->with(['avaliacoes' => fn ($q) => $q->whereIn('docente_id', $bancaIds)])
            ->get();

        if ($totalBanca === 0 || $edital->criterio_nota_corte === Edital::CORTE_APROVACAO_MANUAL) {
            foreach ($inscricoes as $inscricao) {
                $this->setStatus($inscricao, Inscricao::STATUS_HOMOLOGADA);
            }
            return;
        }

        $completas = $inscricoes
            ->map(function (Inscricao $inscricao) use ($totalBanca) {
                $avaliadas = $inscricao->avaliacoes->whereNotNull('nota');
                if ($avaliadas->count() < $totalBanca) {
                    return [
                        'inscricao' => $inscricao,
                        'completa' => false,
                        'media' => null,
                    ];
                }

                return [
                    'inscricao' => $inscricao,
                    'completa' => true,
                    'media' => (float) $avaliadas->avg('nota'),
                ];
            })
            ->values();

        foreach ($completas->where('completa', false) as $item) {
            $this->setStatus($item['inscricao'], Inscricao::STATUS_HOMOLOGADA);
        }

        $completasOnly = $completas->where('completa', true)->values();
        if ($completasOnly->isEmpty()) {
            return;
        }

        if ($edital->criterio_nota_corte === Edital::CORTE_FIXA) {
            $notaCorte = (float) ($edital->nota_corte_fixa ?? 0);
            foreach ($completasOnly as $item) {
                $this->setStatus(
                    $item['inscricao'],
                    $item['media'] >= $notaCorte
                        ? Inscricao::STATUS_PRE_APROVADA
                        : Inscricao::STATUS_PRE_INDEFERIDA
                );
            }
            return;
        }

        if ($edital->criterio_nota_corte === Edital::CORTE_MEDIA_FLUTUANTE) {
            $mediaGeral = (float) $completasOnly->avg('media');
            $offset = (float) ($edital->nota_corte_offset ?? 0);
            $limite = $mediaGeral + $offset;
            foreach ($completasOnly as $item) {
                $this->setStatus(
                    $item['inscricao'],
                    $item['media'] >= $limite
                        ? Inscricao::STATUS_PRE_APROVADA
                        : Inscricao::STATUS_PRE_INDEFERIDA
                );
            }
            return;
        }

        if ($edital->criterio_nota_corte === Edital::CORTE_NUMERO_VAGAS) {
            $vagas = max(0, (int) ($edital->numero_vagas ?? 0));
            $ordenadas = $completasOnly->all();
            usort($ordenadas, function ($a, $b) {
                if ((float) $a['media'] === (float) $b['media']) {
                    return (int) $a['inscricao']->id <=> (int) $b['inscricao']->id;
                }
                return (float) $b['media'] <=> (float) $a['media'];
            });
            foreach ($ordenadas as $index => $item) {
                $this->setStatus(
                    $item['inscricao'],
                    ($vagas > 0 && $index < $vagas)
                        ? Inscricao::STATUS_PRE_APROVADA
                        : Inscricao::STATUS_PRE_INDEFERIDA
                );
            }
        }
    }

    private function setStatus(Inscricao $inscricao, string $status): void
    {
        $inscricao->forceFill([
            'status' => $status,
            'decided_at' => null,
            'decided_by' => null,
            'indeferimento_motivo' => null,
        ])->save();
    }
}
