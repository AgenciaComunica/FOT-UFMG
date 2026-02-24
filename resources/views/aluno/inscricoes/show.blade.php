<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Inscrição {{ $inscricao->protocolo }}</h2>
            <p class="text-sm text-slate-500">{{ $inscricao->edital?->titulo }}</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        @php
            $statusClass = match($inscricao->status) {
                'HOMOLOGADA' => 'status-homologada',
                'INDEFERIDA' => 'status-indeferida',
                default => 'status-recebida',
            };
            $statusLabel = match($inscricao->status) {
                'HOMOLOGADA' => 'Homologada',
                'INDEFERIDA' => 'Indeferida',
                default => 'Em Análise',
            };
        @endphp

        <div class="panel-card space-y-2 text-sm text-slate-700">
            <p><strong>Status:</strong> <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></p>
            <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
            <p><strong>Email:</strong> {{ $inscricao->email }}</p>
            <p><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
            <p><strong>Motivo indeferimento:</strong> {{ $inscricao->indeferimento_motivo ?: '-' }}</p>
        </div>

        <div class="panel-card">
            <h3 class="text-base font-semibold text-slate-800">Documentos enviados</h3>
            <ul class="mt-3 space-y-2">
                @foreach ($inscricao->documentos as $doc)
                    <li class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm">
                        <span>{{ $doc->tipo }} ({{ $doc->original_name }})</span>
                        <a href="{{ route('aluno.documentos.download', $doc) }}" class="text-blue-600 hover:underline">Download</a>
                    </li>
                @endforeach
            </ul>
        </div>

        <a href="{{ route('aluno.inscricoes.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">Voltar</a>
    </div>
</x-app-layout>
