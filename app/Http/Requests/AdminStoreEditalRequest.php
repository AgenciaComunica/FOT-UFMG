<?php

namespace App\Http\Requests;

use App\Models\InscricaoDocumento;
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
            'periodo_inscricao_inicio' => ['required', 'date'],
            'periodo_inscricao_fim' => ['required', 'date', 'after_or_equal:periodo_inscricao_inicio'],
            'documentos_requeridos' => ['required', 'array', 'min:1'],
            'documentos_requeridos.*.tipo' => ['required', 'string', 'in:'.implode(',', InscricaoDocumento::TIPOS)],
            'documentos_requeridos.*.descricao' => ['nullable', 'string', 'max:255'],
            'documentos_requeridos.*.obrigatorio' => ['nullable', 'boolean'],
            'documentos_requeridos.*.ordem' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasObrigatorio = collect($this->input('documentos_requeridos', []))
                ->contains(fn (array $item): bool => (bool) ($item['obrigatorio'] ?? false));

            if (! $hasObrigatorio) {
                $validator->errors()->add('documentos_requeridos', 'Marque pelo menos um documento como obrigatório.');
            }
        });
    }
}
