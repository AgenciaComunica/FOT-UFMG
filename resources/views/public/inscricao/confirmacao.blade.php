<x-guest-layout>
    <div class="panel-card space-y-4 text-center">
        <p class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-xl text-emerald-700">✓</p>
        <h1 class="text-2xl font-bold text-slate-900">Inscrição recebida com sucesso</h1>
        <p class="text-sm text-slate-600"><strong>Edital:</strong> {{ $edital->titulo }}</p>

        <div class="rounded-lg bg-slate-100 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Protocolo</p>
            <p class="mt-1 text-lg font-extrabold text-slate-900">{{ $protocolo }}</p>
        </div>

        <p class="text-sm text-slate-600">Guarde este protocolo para acompanhamento.</p>
        <a href="{{ route('home') }}" class="btn-muted">Voltar para início</a>
    </div>
</x-guest-layout>
