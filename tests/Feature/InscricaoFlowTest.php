<?php

namespace Tests\Feature;

use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InscricaoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_inscricao_cria_registro_e_salva_documentos(): void
    {
        Storage::fake('local');

        $payload = [
            'nome_completo' => 'Aluno Exemplo',
            'email' => 'aluno@example.com',
            'cpf' => '12345678900',
            'telefone' => '31999999999',
            'documentos' => $this->fakeDocumentos(),
        ];

        $response = $this->post(route('inscricao.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('inscricoes', [
            'email' => 'aluno@example.com',
            'status' => Inscricao::STATUS_RECEBIDA,
        ]);

        $inscricao = Inscricao::query()->where('email', 'aluno@example.com')->firstOrFail();

        foreach (InscricaoDocumento::TIPOS as $tipo) {
            $this->assertDatabaseHas('inscricao_documentos', [
                'inscricao_id' => $inscricao->id,
                'tipo' => $tipo,
            ]);
            Storage::disk('local')->assertExists('inscricoes/'.$inscricao->id.'/'.$tipo.'.pdf');
        }
    }

    public function test_nao_autenticado_nao_acessa_secretaria(): void
    {
        $this->get(route('secretaria.inscricoes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_secretaria_aprova_inscricao_e_cria_usuario_aluno(): void
    {
        $secretaria = User::factory()->create([
            'role' => User::ROLE_SECRETARIA,
        ]);

        $inscricao = Inscricao::factory()->create([
            'email' => 'aprovacao@example.com',
            'status' => Inscricao::STATUS_RECEBIDA,
        ]);

        $response = $this->actingAs($secretaria)
            ->post(route('secretaria.inscricoes.aprovar', $inscricao));

        $response->assertRedirect(route('secretaria.inscricoes.show', $inscricao));

        $inscricao->refresh();

        $this->assertSame(Inscricao::STATUS_APROVADA, $inscricao->status);
        $this->assertNotNull($inscricao->decided_at);
        $this->assertSame($secretaria->id, $inscricao->decided_by);
        $this->assertNotNull($inscricao->user_id);

        $this->assertDatabaseHas('users', [
            'id' => $inscricao->user_id,
            'email' => 'aprovacao@example.com',
            'role' => User::ROLE_ALUNO,
        ]);
    }

    public function test_aluno_acessa_painel_mas_nao_acessa_secretaria(): void
    {
        $aluno = User::factory()->create([
            'role' => User::ROLE_ALUNO,
        ]);

        Inscricao::factory()->create([
            'user_id' => $aluno->id,
            'email' => $aluno->email,
            'status' => Inscricao::STATUS_APROVADA,
        ]);

        $this->actingAs($aluno)
            ->get(route('aluno.painel'))
            ->assertOk();

        $this->actingAs($aluno)
            ->get(route('secretaria.inscricoes.index'))
            ->assertForbidden();
    }

    public function test_downloads_respeitam_permissoes_de_secretaria_e_aluno(): void
    {
        Storage::fake('local');

        $secretaria = User::factory()->create(['role' => User::ROLE_SECRETARIA]);
        $alunoA = User::factory()->create(['role' => User::ROLE_ALUNO]);
        $alunoB = User::factory()->create(['role' => User::ROLE_ALUNO]);

        $inscricaoA = Inscricao::factory()->create([
            'user_id' => $alunoA->id,
            'email' => $alunoA->email,
            'status' => Inscricao::STATUS_APROVADA,
        ]);

        $pathA = 'inscricoes/'.$inscricaoA->id.'/'.InscricaoDocumento::DOCUMENTO_FOTO.'.pdf';
        Storage::disk('local')->put($pathA, 'pdf-content');

        $docA = InscricaoDocumento::factory()->create([
            'inscricao_id' => $inscricaoA->id,
            'tipo' => InscricaoDocumento::DOCUMENTO_FOTO,
            'arquivo_path' => $pathA,
            'original_name' => 'foto.pdf',
        ]);

        $this->actingAs($secretaria)
            ->get(route('secretaria.inscricoes.documentos.download', [$inscricaoA, $docA]))
            ->assertOk();

        $this->actingAs($alunoA)
            ->get(route('aluno.documentos.download', $docA))
            ->assertOk();

        $this->actingAs($alunoB)
            ->get(route('aluno.documentos.download', $docA))
            ->assertForbidden();
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function fakeDocumentos(): array
    {
        $docs = [];

        foreach (InscricaoDocumento::TIPOS as $tipo) {
            $docs[$tipo] = UploadedFile::fake()->create(strtolower($tipo).'.pdf', 100, 'application/pdf');
        }

        return $docs;
    }
}
