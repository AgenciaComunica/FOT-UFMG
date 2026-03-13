<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicLeadController extends Controller
{
    public function iframe(): View
    {
        return view('public.leads.iframe', [
            'honeypotField' => config('inscricoes.honeypot_field', 'website'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $honeypotField = (string) config('inscricoes.honeypot_field', 'website');

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            $honeypotField => ['nullable', 'size:0'],
        ], [
            'nome.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

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

        return redirect()
            ->route('public.leads.iframe')
            ->with('status', 'Agradecemos pelo cadastro.');
    }
}
