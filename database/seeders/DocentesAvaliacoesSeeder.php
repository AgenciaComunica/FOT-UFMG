<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\InscricaoAvaliacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocentesAvaliacoesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('pt_BR');

        $docentes = User::query()
            ->where('role', User::ROLE_DOCENTE)
            ->orderBy('id')
            ->get();

        $faltantes = max(0, 15 - $docentes->count());
        for ($i = 1; $i <= $faltantes; $i++) {
            User::query()->create([
                'name' => $faker->name(),
                'email' => 'docente'.str_pad((string) ($docentes->count() + $i), 2, '0', STR_PAD_LEFT).'@teste.com',
                'telefone' => preg_replace('/\D+/', '', (string) $faker->cellphone(false)) ?: null,
                'password' => Str::password(18),
                'role' => User::ROLE_DOCENTE,
                'ativo' => true,
                'email_verified_at' => now(),
            ]);
        }

        $docentes = User::query()
            ->where('role', User::ROLE_DOCENTE)
            ->where('ativo', true)
            ->inRandomOrder()
            ->get();

        $inscricoes = Edital::query()
            ->with(['inscricoes', 'docentesBanca'])
            ->get()
            ->flatMap(function (Edital $edital) use ($docentes) {
                if ($edital->inscricoes->isEmpty()) {
                    return collect();
                }

                $bancaAtual = $edital->docentesBanca;
                if ($bancaAtual->isEmpty()) {
                    $qtdBanca = min(max(3, random_int(3, 5)), $docentes->count());
                    $selecionados = $docentes->random($qtdBanca)->values();
                    $sync = [];
                    foreach ($selecionados as $idx => $docente) {
                        $sync[$docente->id] = ['ordem' => $idx + 1];
                    }
                    $edital->docentesBanca()->sync($sync);
                    $bancaAtual = $edital->docentesBanca()->get();
                }

                return $edital->inscricoes->map(fn ($inscricao) => [
                    'inscricao' => $inscricao,
                    'banca' => $bancaAtual,
                ]);
            });

        InscricaoAvaliacao::query()->delete();

        $comentarios = [
            'Currículo compatível com os critérios do edital.',
            'Boa aderência às exigências da banca.',
            'Necessita complementar experiência prática.',
            'Documentação e histórico bem apresentados.',
            'Perfil acadêmico consistente para a vaga.',
        ];

        foreach ($inscricoes as $item) {
            $inscricao = $item['inscricao'];
            $banca = $item['banca'];

            foreach ($banca as $docente) {
                $avaliado = random_int(1, 100) <= 70;

                InscricaoAvaliacao::query()->create([
                    'inscricao_id' => $inscricao->id,
                    'docente_id' => $docente->id,
                    'nota' => $avaliado ? number_format(random_int(0, 1000) / 100, 2, '.', '') : null,
                    'comentario' => $avaliado ? $comentarios[array_rand($comentarios)] : null,
                    'avaliado_at' => $avaliado ? now()->subDays(random_int(0, 10)) : null,
                ]);
            }
        }
    }
}
