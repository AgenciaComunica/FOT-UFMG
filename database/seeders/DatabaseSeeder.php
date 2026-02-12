<?php

namespace Database\Seeders;

use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([SecretariaUserSeeder::class]);

        $inscricao = Inscricao::factory()->create();

        foreach (InscricaoDocumento::TIPOS as $tipo) {
            InscricaoDocumento::factory()->create([
                'inscricao_id' => $inscricao->id,
                'tipo' => $tipo,
                'arquivo_path' => 'inscricoes/'.$inscricao->id.'/'.$tipo.'.pdf',
                'original_name' => strtolower($tipo).'.pdf',
            ]);
        }
    }
}
