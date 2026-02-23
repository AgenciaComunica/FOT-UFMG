<?php

namespace App\Http\Requests;

use App\Models\Edital;
use App\Models\InscricaoDocumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicStoreInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxPdfKb = (int) config('inscricoes.max_pdf_kb', 10_240);

        return [
            'nome_completo' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'cpf' => ['required', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'documentos' => ['required', 'array'],
            'documentos.*' => [
                'nullable',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:'.$maxPdfKb,
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $honeypotField = config('inscricoes.honeypot_field', 'website');

            if (filled($this->input($honeypotField))) {
                $validator->errors()->add('form', 'Não foi possível processar a inscrição.');
            }

            /** @var Edital|null $edital */
            $edital = $this->route('edital');
            if (! $edital || ! $edital->isAberto()) {
                $validator->errors()->add('edital', 'As inscrições para este edital estão encerradas.');

                return;
            }

            $requiredTipos = $edital->documentosRequeridos
                ->where('obrigatorio', true)
                ->pluck('tipo');

            foreach ($requiredTipos as $tipo) {
                if (! $this->hasFile('documentos.'.$tipo)) {
                    $validator->errors()->add('documentos.'.$tipo, 'Documento obrigatório não enviado.');
                }
            }

            $invalidTipos = collect(array_keys($this->file('documentos', [])))
                ->filter(fn (string $tipo): bool => ! in_array($tipo, InscricaoDocumento::TIPOS, true));

            if ($invalidTipos->isNotEmpty()) {
                $validator->errors()->add('documentos', 'Tipo de documento inválido enviado.');
            }
        });
    }
}
