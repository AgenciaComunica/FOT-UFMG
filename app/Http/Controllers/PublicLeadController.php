<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;

class PublicLeadController extends Controller
{
    public function iframe(): View
    {
        return $this->renderIframe();
    }

    public function store(Request $request): View
    {
        $honeypotField = (string) config('inscricoes.honeypot_field', 'website');

        $validator = Validator::make($request->all(), [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            $honeypotField => ['nullable', 'size:0'],
        ], [
            'nome.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        if ($validator->fails()) {
            return $this->renderIframe(
                formErrors: $validator->errors(),
                oldInput: $request->only(['nome', 'email'])
            );
        }

        $data = $validator->validated();

        $email = mb_strtolower(trim((string) $data['email']));
        $nome = trim((string) $data['nome']);

        $lead = Lead::query()->firstWhere('email', $email);

        if ($lead) {
            $lead->forceFill([
                'nome' => $nome,
                'updated_at' => Carbon::now(),
            ])->save();
        } else {
            Lead::query()->create([
                'nome' => $nome,
                'email' => $email,
            ]);
        }

        return $this->renderIframe(
            statusMessage: 'Agradecemos pelo cadastro.'
        );
    }

    private function renderIframe(?string $statusMessage = null, ?MessageBag $formErrors = null, array $oldInput = []): View
    {
        return view('public.leads.iframe', [
            'honeypotField' => config('inscricoes.honeypot_field', 'website'),
            'statusMessage' => $statusMessage,
            'formErrors' => $formErrors ?? new MessageBag(),
            'oldInput' => $oldInput,
        ]);
    }
}
