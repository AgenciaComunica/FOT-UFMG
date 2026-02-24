<?php

namespace Database\Factories;

use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inscricao>
 */
class InscricaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'edital_id' => Edital::factory(),
            'protocolo' => strtoupper(Str::random(12)),
            'nome_completo' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => fake()->numerify('###########'),
            'telefone' => fake()->phoneNumber(),
            'status' => Inscricao::STATUS_RECEBIDA,
            'submitted_at' => now(),
        ];
    }
}
