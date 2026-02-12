<?php

namespace Database\Factories;

use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InscricaoDocumento>
 */
class InscricaoDocumentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inscricao_id' => Inscricao::factory(),
            'tipo' => fake()->randomElement(InscricaoDocumento::TIPOS),
            'arquivo_path' => 'inscricoes/1/documento.pdf',
            'original_name' => 'documento.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(1_000, 200_000),
            'uploaded_at' => now(),
        ];
    }
}
