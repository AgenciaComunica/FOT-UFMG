<?php

use App\Mail\EditalEncerradoDocentesMail;
use App\Mail\InscricaoResultadoMail;
use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inscricoes:processar-encerramentos', function () {
    $now = now();
    $editais = Edital::query()
        ->where('publicado', true)
        ->where('periodo_inscricao_fim', '<', $now)
        ->whereNull('inscricoes_encerramento_notificado_at')
        ->with('docentesBanca')
        ->get();

    $totalEditais = 0;
    $totalDocentesNotificados = 0;
    $totalIndeferidas = 0;

    foreach ($editais as $edital) {
        $totalEditais++;

        foreach ($edital->docentesBanca->where('ativo', true) as $docente) {
            try {
                Mail::to($docente->email)->send(new EditalEncerradoDocentesMail($edital));
            } catch (\Throwable) {
            }
            $totalDocentesNotificados++;
        }

        $inscricoesNaoVerificadas = Inscricao::query()
            ->where('edital_id', $edital->id)
            ->where('status', Inscricao::STATUS_RECEBIDA)
            ->whereNull('email_verified_at')
            ->get();

        foreach ($inscricoesNaoVerificadas as $inscricao) {
            $inscricao->update([
                'status' => Inscricao::STATUS_INDEFERIDA,
                'decided_at' => now(),
                'indeferimento_motivo' => 'E-mail incorreto ou não verificado até o encerramento das inscrições.',
            ]);

            if (filled($inscricao->email)) {
                try {
                    Mail::to($inscricao->email)->send(
                        new InscricaoResultadoMail(
                            $inscricao->fresh(['edital']),
                            'Não aprovado/Indeferido',
                            route('home', ['tab' => 'verificar'])
                        )
                    );
                } catch (\Throwable) {
                }
            }

            $inscricao->forceFill([
                'resultado_email_sent_at' => now(),
            ])->save();

            $totalIndeferidas++;
        }

        $edital->forceFill([
            'inscricoes_encerramento_notificado_at' => now(),
        ])->save();
    }

    $this->info("Processamento finalizado. Editais: {$totalEditais}; docentes notificados: {$totalDocentesNotificados}; inscrições indeferidas: {$totalIndeferidas}.");
})->purpose('Notifica docentes e finaliza inscrições sem e-mail verificado após encerramento');

Schedule::command('inscricoes:processar-encerramentos')->hourly();
