<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Minhas inscrições</h2>
            <p class="text-sm text-slate-500">Histórico de inscrições vinculadas ao seu perfil.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Edital</th>
                        <th>Status</th>
                        <th>Enviada em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inscricoes as $inscricao)
                        @php
                            $statusClass = match($inscricao->status) {
                                'HOMOLOGADA' => 'status-homologada',
                                'INDEFERIDA' => 'status-indeferida',
                                default => 'status-recebida',
                            };
                        @endphp
                        <tr>
                            <td class="font-semibold text-slate-700">{{ $inscricao->protocolo }}</td>
                            <td>{{ $inscricao->edital?->titulo }}</td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $inscricao->status }}</span></td>
                            <td>{{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('aluno.inscricoes.show', $inscricao) }}" class="text-blue-600 hover:underline">Detalhes</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-slate-500">Nenhuma inscrição encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $inscricoes->links() }}</div>
    </div>
</x-app-layout>
