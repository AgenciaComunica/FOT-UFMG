<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TesteLembreteVerificacaoSeeder extends Seeder
{
    public function run(): void
    {
        $editalAberto = Edital::query()->updateOrCreate(
            ['titulo' => 'EDITAL TESTE LEMBRETE E-MAIL'],
            [
                'descricao' => 'Edital aberto para validar o disparo de lembrete de verificação de e-mail.',
                'publicado' => true,
                'periodo_inscricao_inicio' => now()->subDays(2)->setTime(8, 0),
                'periodo_inscricao_fim' => now()->addDays(10)->setTime(23, 59),
                'archived_at' => null,
                'archived_by' => null,
            ]
        );

        $editalEncerrado = Edital::query()->updateOrCreate(
            ['titulo' => 'EDITAL TESTE LEMBRETE ENCERRADO'],
            [
                'descricao' => 'Edital encerrado para validar que o lembrete não será disparado.',
                'publicado' => true,
                'periodo_inscricao_inicio' => now()->subDays(20)->setTime(8, 0),
                'periodo_inscricao_fim' => now()->subDays(5)->setTime(23, 59),
                'archived_at' => null,
                'archived_by' => null,
            ]
        );

        $this->upsertInscricao(
            $editalAberto,
            'TESTE-EMAIL-0001',
            'Candidato Email Pendente 1',
            'teste.email.pendente1@teste.com',
            '11111111111',
            false
        );

        $this->upsertInscricao(
            $editalAberto,
            'TESTE-EMAIL-0002',
            'Candidato Email Pendente 2',
            'teste.email.pendente2@teste.com',
            '22222222222',
            false
        );

        $this->upsertInscricao(
            $editalAberto,
            'TESTE-EMAIL-0003',
            'Candidato Email Verificado',
            'teste.email.verificado@teste.com',
            '33333333333',
            true
        );

        $this->upsertInscricao(
            $editalEncerrado,
            'TESTE-EMAIL-0004',
            'Candidato Edital Encerrado',
            'teste.email.encerrado@teste.com',
            '44444444444',
            false
        );
    }

    private function upsertInscricao(
        Edital $edital,
        string $protocolo,
        string $nome,
        string $email,
        string $cpf,
        bool $emailVerificado,
    ): void {
        $token = $emailVerificado ? null : hash('sha256', Str::uuid().'|'.$email);
        $submittedAt = Carbon::now()->subHours(random_int(2, 72));

        Inscricao::query()->updateOrCreate(
            ['protocolo' => $protocolo],
            [
                'edital_id' => $edital->id,
                'nome_completo' => $nome,
                'email' => $email,
                'cpf' => $cpf,
                'telefone' => '31999990000',
                'status' => Inscricao::STATUS_RECEBIDA,
                'submitted_at' => $submittedAt,
                'email_verification_token' => $token,
                'verification_sent_at' => $submittedAt,
                'email_verified_at' => $emailVerificado ? now()->subHour() : null,
                'edit_link_token' => null,
                'edit_link_sent_at' => null,
                'edit_link_expires_at' => null,
                'edit_link_used_at' => null,
                'decided_at' => null,
                'decided_by' => null,
                'indeferimento_motivo' => null,
            ]
        );
    }
}
