<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Validator;

class AdminStoreEditalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'publicado' => ['nullable', 'boolean'],
            'arquivo_edital' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'periodo_inscricao_inicio' => ['required', 'date'],
            'periodo_inscricao_fim' => ['required', 'date', 'after_or_equal:periodo_inscricao_inicio'],
            'banca_docentes' => ['nullable', 'array'],
            'banca_docentes.*' => ['nullable', 'integer', 'exists:users,id'],
            'documentos_requeridos' => ['nullable', 'array'],
            'documentos_requeridos.*.tipo' => ['required_with:documentos_requeridos', 'string', 'max:120'],
            'documentos_requeridos.*.formatos_aceitos' => ['required_with:documentos_requeridos', 'array', 'min:1'],
            'documentos_requeridos.*.formatos_aceitos.*' => ['required_with:documentos_requeridos', 'in:pdf,docx,jpg,png'],
            'documentos_requeridos.*.descricao' => ['nullable', 'string', 'max:255'],
            'documentos_requeridos.*.obrigatorio' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Informe o título do edital.',
            'periodo_inscricao_inicio.required' => 'Informe a data/hora de início da inscrição.',
            'periodo_inscricao_fim.required' => 'Informe a data/hora de fim da inscrição.',
            'periodo_inscricao_fim.after_or_equal' => 'A data/hora de fim deve ser igual ou posterior ao início.',
            'arquivo_edital.mimes' => 'O arquivo do edital deve estar em PDF.',
            'banca_docentes.*.exists' => 'Selecione apenas docentes válidos para a banca.',
            'documentos_requeridos.*.tipo.required' => 'Informe o nome de cada documento.',
            'documentos_requeridos.*.formatos_aceitos.required' => 'Selecione ao menos um formato aceito para cada documento.',
            'documentos_requeridos.*.formatos_aceitos.min' => 'Selecione ao menos um formato aceito para cada documento.',
            'documentos_requeridos.*.formatos_aceitos.*.in' => 'Formato inválido selecionado. Use PDF, DOCX, JPG ou PNG.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $documentos = collect($this->input('documentos_requeridos', []))
                ->pluck('tipo')
                ->map(fn ($tipo) => mb_strtolower(trim((string) $tipo)))
                ->filter();

            if ($documentos->count() !== $documentos->unique()->count()) {
                $validator->errors()->add('documentos_requeridos', 'Não repita o nome do documento.');
            }

            $docentesIds = collect($this->input('banca_docentes', []))
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($docentesIds->count() !== $docentesIds->unique()->count()) {
                $validator->errors()->add('banca_docentes', 'Não repita o mesmo docente na banca.');
                return;
            }

            if ($docentesIds->isNotEmpty()) {
                $validos = User::query()
                    ->whereIn('id', $docentesIds)
                    ->where('role', User::ROLE_DOCENTE)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                if ($validos->count() !== $docentesIds->count()) {
                    $validator->errors()->add('banca_docentes', 'A banca deve conter apenas usuários do tipo docente.');
                }
            }

            if (! $this->boolean('publicado')) {
                return;
            }

            if ($docentesIds->count() < 1) {
                $validator->errors()->add('banca_docentes', 'Informe ao menos 1 docente na banca para publicar o edital.');
            }

            if (! filled($this->input('descricao'))) {
                $validator->errors()->add('descricao', 'Descrição é obrigatória para publicar o edital.');
            }

            $edital = $this->route('edital');
            $hasArquivoAtual = $edital && filled($edital->arquivo_path);

            if (! $this->hasFile('arquivo_edital') && ! $hasArquivoAtual) {
                $validator->errors()->add('arquivo_edital', 'Arquivo PDF do edital é obrigatório para publicação.');
            }
        });
    }
}
