<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Inscrições do Edital</h2>
            <p class="text-sm text-slate-500">{{ $edital->titulo }}</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="{ showFilters: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" class="btn-muted" @click="showFilters = !showFilters">Filtros</button>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.editais.relatorios.inscricoes-recebidas', $edital) }}" class="btn-muted">Exportar CSV recebidas</a>
                <a href="{{ route('admin.editais.relatorios.inscricoes-homologadas', $edital) }}" class="btn-success">Exportar CSV homologadas</a>
            </div>
        </div>

        <form method="GET" x-show="showFilters" x-transition class="panel-card grid gap-3 md:grid-cols-4 md:items-end">
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="input-base">
                    <option value="">Todos</option>
                    <option value="RECEBIDA" @selected($status === 'RECEBIDA')>RECEBIDA</option>
                    <option value="HOMOLOGADA" @selected($status === 'HOMOLOGADA')>HOMOLOGADA</option>
                    <option value="INDEFERIDA" @selected($status === 'INDEFERIDA')>INDEFERIDA</option>
                </select>
            </div>
            <div>
                <x-input-label for="data" value="Data envio" />
                <x-text-input id="data" name="data" type="date" class="input-base" :value="$date" />
            </div>
            <div>
                <x-input-label for="q" value="Busca (nome/email/cpf)" />
                <x-text-input id="q" name="q" type="text" class="input-base" :value="$search" />
            </div>
            <div>
                <x-primary-button>Aplicar filtros</x-primary-button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Nome</th>
                        <th>Email</th>
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
                            <td>{{ $inscricao->nome_completo }}</td>
                            <td>{{ $inscricao->email }}</td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $inscricao->status }}</span></td>
                            <td>{{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.inscricoes.show', $inscricao) }}" class="text-blue-600 hover:underline">Ver detalhe</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-slate-500">Nenhuma inscrição encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $inscricoes->links() }}</div>
    </div>
</x-app-layout>
