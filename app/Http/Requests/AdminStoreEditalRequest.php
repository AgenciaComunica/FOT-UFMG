<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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

            if (! $this->boolean('publicado')) {
                return;
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
