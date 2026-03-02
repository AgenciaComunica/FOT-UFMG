<?php

namespace App\Http\Requests;

use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique(Inscricao::class, 'email')],
            'cpf' => ['required', 'string', 'max:20', Rule::unique(Inscricao::class, 'cpf')],
            'telefone' => ['nullable', 'string', 'max:30'],
            'inicio_programa_semestre' => ['required', 'integer', 'in:1,2'],
            'inicio_programa_ano' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 10)],
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['nullable', 'file', 'max:'.$maxPdfKb],
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

            $edital->loadMissing('documentosRequeridos');
            $requiredDocs = $edital->documentosRequeridos->where('obrigatorio', true);

            foreach ($requiredDocs as $doc) {
                if (! $this->hasFile('documentos.'.$doc->id)) {
                    $validator->errors()->add('documentos.'.$doc->id, 'Documento obrigatório não enviado.');
                }
            }

            $docsById = $edital->documentosRequeridos->keyBy('id');
            $uploaded = collect($this->file('documentos', []));
            $invalidIds = $uploaded->keys()->filter(fn ($id): bool => ! $docsById->has((int) $id));

            if ($invalidIds->isNotEmpty()) {
                $validator->errors()->add('documentos', 'Documento inválido enviado.');
            }

            foreach ($uploaded as $docId => $file) {
                if (! $file) {
                    continue;
                }

                $doc = $docsById->get((int) $docId);
                if (! $doc) {
                    continue;
                }

                $formatosAceitos = $doc->formatos_aceitos;
                $extensao = strtolower((string) $file->getClientOriginalExtension());
                if ($extensao === 'jpeg') {
                    $extensao = 'jpg';
                }

                if (! in_array($extensao, $formatosAceitos, true)) {
                    $validator->errors()->add('documentos.'.$docId, 'Formato inválido. Permitidos: '.strtoupper(implode(', ', $formatosAceitos)).'.');
                    continue;
                }

                $mime = (string) $file->getMimeType();
                $mimeValidoPorExt = [
                    'pdf' => ['application/pdf', 'application/x-pdf'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                    ],
                    'jpg' => ['image/jpeg', 'image/jpg'],
                    'png' => ['image/png'],
                ];

                if (isset($mimeValidoPorExt[$extensao]) && ! in_array($mime, $mimeValidoPorExt[$extensao], true)) {
                    $validator->errors()->add('documentos.'.$docId, 'MIME inválido para o formato enviado.');
                }
            }

            $cpfDigits = preg_replace('/\D+/', '', (string) $this->input('cpf'));
            if ($cpfDigits !== '') {
                $cpfExists = Inscricao::query()
                    ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', ''), ' ', '') = ?", [$cpfDigits])
                    ->exists();

                if ($cpfExists) {
                    $validator->errors()->add('cpf', 'Este CPF já possui uma inscrição cadastrada. Em caso de erro, entre em contato com a secretaria.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já possui uma inscrição cadastrada. Em caso de erro, entre em contato com a secretaria.',
            'cpf.unique' => 'Este CPF já possui uma inscrição cadastrada. Em caso de erro, entre em contato com a secretaria.',
            'inicio_programa_semestre.required' => 'Selecione o semestre desejado para início.',
            'inicio_programa_semestre.in' => 'Semestre inválido. Selecione 1º ou 2º semestre.',
            'inicio_programa_ano.required' => 'Selecione o ano desejado para início.',
            'inicio_programa_ano.min' => 'Ano de início inválido.',
            'inicio_programa_ano.max' => 'Ano de início inválido.',
        ];
    }
}
