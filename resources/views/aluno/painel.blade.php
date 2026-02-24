<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Painel do Aluno</h2>
            <p class="text-sm text-slate-500">Acompanhe status e documentos da sua inscrição.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <div class="panel-card">
            @if ($ultimaInscricao)
                @php
                    $statusClass = match($ultimaInscricao->status) {
                        'HOMOLOGADA' => 'status-homologada',
                        'INDEFERIDA' => 'status-indeferida',
                        default => 'status-recebida',
                    };
                    $statusLabel = match($ultimaInscricao->status) {
                        'HOMOLOGADA' => 'Homologada',
                        'INDEFERIDA' => 'Indeferida',
                        default => 'Em Análise',
                    };
                @endphp
                <p class="text-sm text-slate-600"><strong>Edital:</strong> {{ $ultimaInscricao->edital?->titulo }}</p>
                <p class="mt-1 text-sm text-slate-600"><strong>Protocolo:</strong> {{ $ultimaInscricao->protocolo }}</p>
                <p class="mt-2"><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></p>
            @else
                <p class="text-sm text-slate-600">Nenhuma inscrição vinculada ao seu usuário.</p>
            @endif

            <a href="{{ route('aluno.inscricoes.index') }}" class="mt-4 inline-flex text-sm font-semibold text-blue-600 hover:underline">Ver todas as inscrições</a>
        </div>
    </div>
</x-app-layout>
