<?php

namespace Database\Factories;

use App\Models\Inscricao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inscricao>
 */
class InscricaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
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
