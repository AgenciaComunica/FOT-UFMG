<?php

namespace App\Http\Requests;

use App\Models\Inscricao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminUpdateInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Inscricao|null $inscricao */
        $inscricao = $this->route('inscricao');
        $ignoreId = $inscricao?->id;

        return [
            'nome_completo' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('inscricoes', 'email')->ignore($ignoreId)],
            'cpf' => ['required', 'string', 'max:20', Rule::unique('inscricoes', 'cpf')->ignore($ignoreId)],
            'telefone' => ['nullable', 'string', 'max:30'],
            'inicio_programa_semestre' => ['required', 'integer', 'in:1,2'],
            'inicio_programa_ano' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 10)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está em uso em outra inscrição.',
            'cpf.unique' => 'Este CPF já está em uso em outra inscrição.',
            'inicio_programa_semestre.required' => 'Selecione o semestre desejado para início.',
            'inicio_programa_ano.required' => 'Selecione o ano desejado para início.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Inscricao|null $inscricao */
            $inscricao = $this->route('inscricao');
            $cpfDigits = preg_replace('/\D+/', '', (string) $this->input('cpf'));

            if ($cpfDigits === '') {
                return;
            }

            $exists = Inscricao::query()
                ->when($inscricao, fn ($q) => $q->where('id', '!=', $inscricao->id))
                ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', ''), ' ', '') = ?", [$cpfDigits])
                ->exists();

            if ($exists) {
                $validator->errors()->add('cpf', 'Este CPF já está em uso em outra inscrição.');
            }
        });
    }
}
