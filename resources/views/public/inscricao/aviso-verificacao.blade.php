<x-guest-layout>
    <div class="panel-card space-y-4 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Aviso de verificação de e-mail</h1>
        <p class="text-sm text-slate-600"><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
        <p class="text-sm text-slate-600">Enviamos um link para <strong>{{ $inscricao->email }}</strong>. Verifique seu e-mail para liberar a candidatura para avaliação.</p>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($inscricao->email_verified_at)
            <p class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">E-mail já verificado</p>
        @else
            <div class="flex justify-center">
                <form method="POST" action="{{ route('public.inscricao.email.reenviar', $inscricao) }}" class="w-full max-w-md space-y-3 text-left">
                    @csrf
                    <input type="hidden" name="resend_key" value="{{ $resendKey }}">
                    <div>
                        <label for="email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">E-mail cadastrado</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            class="input-base"
                            value="{{ old('email') }}"
                            placeholder="Digite o e-mail cadastrado"
                            required
                        >
                    </div>
                    <div class="flex justify-center">
                        <button type="submit" class="btn-primary">Reenviar verificação</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="flex justify-center gap-2">
            <a href="{{ route('home', ['tab' => 'verificar']) }}" class="btn-muted">Verificar status da inscrição</a>
            <a href="{{ route('home') }}" class="btn-muted">Voltar para início</a>
        </div>
    </div>
</x-guest-layout>
