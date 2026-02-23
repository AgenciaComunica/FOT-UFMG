<?php

namespace Database\Factories;

use App\Models\Edital;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Edital>
 */
class EditalFactory extends Factory
{
    protected $model = Edital::class;

    public function definition(): array
    {
        return [
            'titulo' => 'Edital '.fake()->year(),
            'descricao' => fake()->sentence(),
            'periodo_inscricao_inicio' => now()->subDay(),
            'periodo_inscricao_fim' => now()->addDays(15)->setTime(23, 59),
        ];
    }
}
