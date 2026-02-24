<x-guest-layout>
    <div class="panel-card space-y-4 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Aviso de verificação de e-mail</h1>
        <p class="text-sm text-slate-600"><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
        <p class="text-sm text-slate-600">Enviamos um link para <strong>{{ $inscricao->email }}</strong>. Verifique seu e-mail para liberar a candidatura para avaliação.</p>

        @if ($inscricao->email_verified_at)
            <p class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">E-mail já verificado</p>
        @else
            <div class="flex justify-center">
                <form method="POST" action="{{ route('public.inscricao.email.reenviar', $inscricao) }}">
                    @csrf
                    <input type="hidden" name="resend_key" value="{{ $resendKey }}">
                    <button type="submit" class="btn-primary">Reenviar verificação</button>
                </form>
            </div>
        @endif

        <div class="flex justify-center gap-2">
            <a href="{{ route('home', ['tab' => 'verificar']) }}" class="btn-muted">Verificar status da inscrição</a>
            <a href="{{ route('home') }}" class="btn-muted">Voltar para início</a>
        </div>
    </div>
</x-guest-layout>
