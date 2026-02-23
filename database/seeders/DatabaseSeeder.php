<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\InscricaoDocumento;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SecretariaUserSeeder::class]);

        $edital = Edital::query()->create([
            'titulo' => 'Edital FOT/UFMG - Turma Atual',
            'descricao' => 'Processo seletivo do curso de Fisioterapia - Ortopedia e Trauma.',
            'periodo_inscricao_inicio' => now()->subDay(),
            'periodo_inscricao_fim' => now()->addDays(15)->setTime(23, 59),
        ]);

        $ordem = 1;
        foreach (InscricaoDocumento::TIPOS as $tipo) {
            $edital->documentosRequeridos()->create([
                'tipo' => $tipo,
                'descricao' => 'Enviar arquivo PDF legível.',
                'obrigatorio' => $tipo !== InscricaoDocumento::HISTORICO_ESCOLAR,
                'ordem' => $ordem++,
            ]);
        }
    }
}
