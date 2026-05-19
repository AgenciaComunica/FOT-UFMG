<?php

namespace Tests\Feature;

use App\Mail\InscricaoRecebidaMail;
use App\Models\Edital;
use App\Models\Inscricao;
use App\Models\InscricaoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InscricaoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscricao_publica_funciona_apenas_com_edital_aberto(): void
    {
        Storage::fake('local');

        $editalAberto = $this->createEditalAberto();
        $payload = $this->payloadInscricaoCompleta($editalAberto);

        $responseOk = $this->post(route('public.inscricao.store', $editalAberto), $payload);
        $responseOk->assertRedirect();

        $this->assertDatabaseHas('inscricoes', [
            'edital_id' => $editalAberto->id,
            'email' => 'aluno@example.com',
            'status' => Inscricao::STATUS_RECEBIDA,
        ]);

        $editalFechado = Edital::factory()->create([
            'periodo_inscricao_inicio' => now()->subDays(10),
            'periodo_inscricao_fim' => now()->subDay(),
        ]);
        $this->attachDocumentosRequeridos($editalFechado);

        $responseClosed = $this->from(route('public.inscricao.create', $editalFechado))
            ->post(route('public.inscricao.store', $editalFechado), $this->payloadInscricaoCompleta($editalFechado, 'fechado@example.com'));

        $responseClosed->assertRedirect(route('public.inscricao.create', $editalFechado));
        $responseClosed->assertSessionHasErrors('edital');
    }

    public function test_admin_nao_homologa_com_documentos_obrigatorios_faltando(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $edital = $this->createEditalAberto();

        $inscricao = Inscricao::factory()->create([
            'edital_id' => $edital->id,
            'status' => Inscricao::STATUS_RECEBIDA,
        ]);

        // Envia apenas 1 doc obrigatório para forçar falha.
        $inscricao->documentos()->create([
            'tipo' => InscricaoDocumento::DOCUMENTO_FOTO,
            'arquivo_path' => 'inscricoes/'.$inscricao->id.'/'.InscricaoDocumento::DOCUMENTO_FOTO.'.pdf',
            'original_name' => 'doc.pdf',
            'mime' => 'application/pdf',
            'size' => 1000,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.inscricoes.show', $inscricao))
            ->post(route('admin.inscricoes.homologar', $inscricao));

        $response->assertRedirect(route('admin.inscricoes.show', $inscricao));
        $response->assertSessionHasErrors('documentos');

        $this->assertSame(Inscricao::STATUS_RECEBIDA, $inscricao->fresh()->status);
    }

    public function test_homologacao_cria_usuario_aluno_e_vincula_inscricao(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $edital = $this->createEditalAberto();

        $inscricao = Inscricao::factory()->create([
            'edital_id' => $edital->id,
            'email' => 'homologacao@example.com',
            'status' => Inscricao::STATUS_RECEBIDA,
        ]);

        foreach ($edital->documentosRequeridos()->where('obrigatorio', true)->pluck('tipo') as $tipo) {
            $inscricao->documentos()->create([
                'tipo' => $tipo,
                'arquivo_path' => 'inscricoes/'.$inscricao->id.'/'.$tipo.'.pdf',
                'original_name' => strtolower($tipo).'.pdf',
                'mime' => 'application/pdf',
                'size' => 1000,
                'uploaded_at' => now(),
            ]);
        }

        $response = $this->actingAs($admin)
            ->post(route('admin.inscricoes.homologar', $inscricao));

        $response->assertRedirect(route('admin.inscricoes.show', $inscricao));

        $inscricao->refresh();

        $this->assertSame(Inscricao::STATUS_HOMOLOGADA, $inscricao->status);
        $this->assertNotNull($inscricao->user_id);
        $this->assertDatabaseHas('users', [
            'id' => $inscricao->user_id,
            'email' => 'homologacao@example.com',
            'role' => User::ROLE_ALUNO,
        ]);
    }

    public function test_aluno_acessa_apenas_proprias_inscricoes_e_nao_acessa_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $alunoA = User::factory()->create(['role' => User::ROLE_ALUNO]);
        $alunoB = User::factory()->create(['role' => User::ROLE_ALUNO]);

        $inscricaoA = Inscricao::factory()->create([
            'user_id' => $alunoA->id,
            'email' => $alunoA->email,
            'status' => Inscricao::STATUS_HOMOLOGADA,
        ]);

        $this->actingAs($alunoA)->get(route('aluno.painel'))->assertOk();
        $this->actingAs($alunoA)->get(route('aluno.inscricoes.show', $inscricaoA))->assertOk();
        $this->actingAs($alunoB)->get(route('aluno.inscricoes.show', $inscricaoA))->assertForbidden();
        $this->actingAs($alunoA)->get(route('admin.editais.index'))->assertForbidden();

        // Garante que admin continua acessando admin e nao acessa area do aluno.
        $this->actingAs($admin)->get(route('admin.editais.index'))->assertOk();
        $this->actingAs($admin)->get(route('aluno.painel'))->assertForbidden();
    }

    public function test_downloads_e_export_csv_respeitam_permissoes_e_filtro_por_edital(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $aluno = User::factory()->create(['role' => User::ROLE_ALUNO]);
        $outroAluno = User::factory()->create(['role' => User::ROLE_ALUNO]);

        $edital = $this->createEditalAberto();

        $inscricao = Inscricao::factory()->create([
            'edital_id' => $edital->id,
            'user_id' => $aluno->id,
            'email' => $aluno->email,
            'status' => Inscricao::STATUS_HOMOLOGADA,
        ]);

        $path = 'inscricoes/'.$inscricao->id.'/'.InscricaoDocumento::DOCUMENTO_FOTO.'.pdf';
        Storage::disk('local')->put($path, 'pdf-content');

        $doc = InscricaoDocumento::factory()->create([
            'inscricao_id' => $inscricao->id,
            'tipo' => InscricaoDocumento::DOCUMENTO_FOTO,
            'arquivo_path' => $path,
            'original_name' => 'foto.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inscricoes.documentos.download', [$inscricao, $doc]))
            ->assertOk();

        $this->actingAs($aluno)
            ->get(route('aluno.documentos.download', $doc))
            ->assertOk();

        $this->actingAs($outroAluno)
            ->get(route('aluno.documentos.download', $doc))
            ->assertForbidden();

        $csv = $this->actingAs($admin)
            ->get(route('admin.editais.relatorios.inscricoes-homologadas', $edital));

        $csv->assertOk();
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($inscricao->protocolo, $csv->streamedContent());
    }

    public function test_admin_pode_enviar_verificacao_de_email_com_edital_encerrado(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $edital = Edital::factory()->create([
            'periodo_inscricao_inicio' => now()->subDays(10),
            'periodo_inscricao_fim' => now()->subDay(),
        ]);

        $inscricao = Inscricao::factory()->create([
            'edital_id' => $edital->id,
            'email' => 'pendente@example.com',
            'email_verified_at' => null,
            'status' => Inscricao::STATUS_RECEBIDA,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inscricoes.index'))
            ->assertOk()
            ->assertSee('verification-inscricao-'.$inscricao->id, false);

        $response = $this->actingAs($admin)
            ->post(route('admin.inscricoes.verificacao', $inscricao));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Lembrete de verificação enviado com sucesso.');

        $inscricao->refresh();

        $this->assertNotNull($inscricao->email_verification_token);
        $this->assertNotNull($inscricao->verification_sent_at);
        Mail::assertSent(InscricaoRecebidaMail::class, fn ($mail) => $mail->hasTo('pendente@example.com'));
    }

    private function createEditalAberto(): Edital
    {
        $edital = Edital::factory()->create([
            'periodo_inscricao_inicio' => now()->subDay(),
            'periodo_inscricao_fim' => now()->addDays(3)->setTime(23, 59),
        ]);

        $this->attachDocumentosRequeridos($edital);

        return $edital;
    }

    private function attachDocumentosRequeridos(Edital $edital): void
    {
        $ordem = 1;
        foreach (InscricaoDocumento::TIPOS as $tipo) {
            $edital->documentosRequeridos()->create([
                'tipo' => $tipo,
                'descricao' => 'Documento '.$tipo,
                'obrigatorio' => $tipo !== InscricaoDocumento::HISTORICO_ESCOLAR,
                'ordem' => $ordem++,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadInscricaoCompleta(Edital $edital, string $email = 'aluno@example.com'): array
    {
        $docs = [];

        foreach ($edital->documentosRequeridos as $docRequerido) {
            $docs[$docRequerido->id] = UploadedFile::fake()->create(strtolower($docRequerido->tipo).'.pdf', 100, 'application/pdf');
        }

        return [
            'nome_completo' => 'Aluno Exemplo',
            'email' => $email,
            'cpf' => '12345678900',
            'telefone' => '31999999999',
            'documentos' => $docs,
        ];
    }
}
