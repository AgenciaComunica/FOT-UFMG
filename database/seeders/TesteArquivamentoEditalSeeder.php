<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoAvaliacao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TesteArquivamentoEditalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SecretariaUserSeeder::class]);

        InscricaoAvaliacao::query()->delete();
        InscricaoDocumento::query()->delete();
        Inscricao::query()->delete();
        Edital::query()->delete();

        User::query()
            ->where('role', '!=', User::ROLE_ADMIN)
            ->delete();

        Storage::disk('local')->deleteDirectory('inscricoes');
        Storage::disk('local')->deleteDirectory('editais');

        $edital = Edital::query()->create([
            'titulo' => 'EDITAL TESTE ARQUIVAMENTO',
            'descricao' => 'Edital encerrado para validar o modal de arquivamento com inscrições e arquivos anexados.',
            'publicado' => true,
            'periodo_inscricao_inicio' => now()->subDays(20)->setTime(8, 0),
            'periodo_inscricao_fim' => now()->subDays(2)->setTime(23, 59),
            'archived_at' => null,
            'archived_by' => null,
        ]);

        $ordem = 1;
        foreach (InscricaoDocumento::TIPOS as $tipo) {
            $edital->documentosRequeridos()->create([
                'tipo' => $tipo,
                'descricao' => 'PDF de teste para cenário de arquivamento.',
                'obrigatorio' => true,
                'ordem' => $ordem++,
            ]);
        }

        $candidatos = [
            ['protocolo' => 'ARQ-TESTE-0001', 'nome' => 'Candidato Arquivamento 1', 'email' => 'arquivamento1@teste.com', 'cpf' => '70111111111'],
            ['protocolo' => 'ARQ-TESTE-0002', 'nome' => 'Candidato Arquivamento 2', 'email' => 'arquivamento2@teste.com', 'cpf' => '70222222222'],
            ['protocolo' => 'ARQ-TESTE-0003', 'nome' => 'Candidato Arquivamento 3', 'email' => 'arquivamento3@teste.com', 'cpf' => '70333333333'],
        ];

        foreach ($candidatos as $index => $candidato) {
            $submittedAt = now()->subDays(10 - $index)->setTime(10 + $index, 30);

            $inscricao = Inscricao::query()->create([
                'edital_id' => $edital->id,
                'protocolo' => $candidato['protocolo'],
                'nome_completo' => $candidato['nome'],
                'email' => $candidato['email'],
                'cpf' => $candidato['cpf'],
                'telefone' => '3199999000'.($index + 1),
                'email_verification_token' => null,
                'verification_sent_at' => $submittedAt,
                'email_verified_at' => $submittedAt->copy()->addHour(),
                'status' => Inscricao::STATUS_RECEBIDA,
                'submitted_at' => $submittedAt,
            ]);

            foreach (InscricaoDocumento::TIPOS as $tipo) {
                $safeTipo = Str::slug($tipo, '_');
                $fileName = $safeTipo.'.pdf';
                $directory = 'inscricoes/'.$inscricao->id;
                $path = $directory.'/'.$fileName;

                Storage::disk('local')->put($path, $this->blankPdf());

                $inscricao->documentos()->create([
                    'tipo' => $tipo,
                    'arquivo_path' => $path,
                    'original_name' => $fileName,
                    'mime' => 'application/pdf',
                    'size' => Storage::disk('local')->size($path),
                    'uploaded_at' => $submittedAt,
                ]);
            }
        }
    }

    private function blankPdf(): string
    {
        return implode("\n", [
            '%PDF-1.1',
            '1 0 obj<<>>endobj',
            '2 0 obj<< /Type /Catalog /Pages 3 0 R >>endobj',
            '3 0 obj<< /Type /Pages /Kids [4 0 R] /Count 1 >>endobj',
            '4 0 obj<< /Type /Page /Parent 3 0 R /MediaBox [0 0 200 200] /Contents 5 0 R >>endobj',
            '5 0 obj<< /Length 0 >>stream',
            'endstream',
            'endobj',
            'xref',
            '0 6',
            '0000000000 65535 f ',
            '0000000010 00000 n ',
            '0000000031 00000 n ',
            '0000000080 00000 n ',
            '0000000137 00000 n ',
            '0000000224 00000 n ',
            'trailer<< /Root 2 0 R /Size 6 >>',
            'startxref',
            '273',
            '%%EOF',
        ]);
    }
}
