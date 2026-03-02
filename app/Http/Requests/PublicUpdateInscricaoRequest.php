<?php

namespace App\Http\Requests;

use App\Models\Edital;
use App\Models\Inscricao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicUpdateInscricaoRequest extends FormRequest
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
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'cpf' => ['required', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'inicio_programa_semestre' => ['required', 'integer', 'in:1,2'],
            'inicio_programa_ano' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 10)],
            'motivo_edicao' => ['required', 'string', 'min:5', 'max:1000'],
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['nullable', 'file', 'max:'.$maxPdfKb],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_edicao.required' => 'Informe o motivo da edição para continuar.',
            'motivo_edicao.min' => 'Descreva o motivo da edição com pelo menos 5 caracteres.',
            'motivo_edicao.max' => 'O motivo da edição pode ter no máximo 1000 caracteres.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Inscricao|null $inscricao */
            $inscricao = $this->route('inscricao');
            if (! $inscricao) {
                $validator->errors()->add('form', 'Inscrição inválida.');
                return;
            }

            /** @var Edital|null $edital */
            $edital = $inscricao->edital()->with('documentosRequeridos')->first();
            if (! $edital || ! $edital->isAberto()) {
                $validator->errors()->add('form', 'A edição só pode ser realizada com o edital aberto.');
                return;
            }

            if (in_array($inscricao->status, [Inscricao::STATUS_HOMOLOGADA, Inscricao::STATUS_INDEFERIDA], true)) {
                $validator->errors()->add('form', 'A inscrição já possui decisão final e não pode ser editada.');
                return;
            }

            $email = mb_strtolower(trim((string) $this->input('email')));
            if ($email !== '') {
                $emailExists = Inscricao::query()
                    ->where('id', '!=', $inscricao->id)
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->exists();

                if ($emailExists) {
                    $validator->errors()->add('email', 'Este e-mail já possui outra inscrição cadastrada.');
                }
            }

            $cpfDigits = preg_replace('/\D+/', '', (string) $this->input('cpf')) ?: '';
            if ($cpfDigits !== '') {
                $cpfExists = Inscricao::query()
                    ->where('id', '!=', $inscricao->id)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), '/', ''), ' ', '') = ?", [$cpfDigits])
                    ->exists();

                if ($cpfExists) {
                    $validator->errors()->add('cpf', 'Este CPF já possui outra inscrição cadastrada.');
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
        });
    }
}
