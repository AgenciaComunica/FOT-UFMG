<?php

namespace App\Http\Requests;

use App\Models\Edital;
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
        });
    }
}
