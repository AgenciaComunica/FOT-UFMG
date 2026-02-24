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
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="input-base">
                    <option value="">Todos</option>
                    <option value="RECEBIDA" @selected($status === 'RECEBIDA')>Em Análise</option>
                    <option value="HOMOLOGADA" @selected($status === 'HOMOLOGADA')>Homologada</option>
                    <option value="INDEFERIDA" @selected($status === 'INDEFERIDA')>Indeferida</option>
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
                            $statusLabel = match($inscricao->status) {
                                'HOMOLOGADA' => 'Homologada',
                                'INDEFERIDA' => 'Indeferida',
                                default => 'Em Análise',
                            };
                        @endphp
                        <tr>
                            <td class="font-semibold text-slate-700">{{ $inscricao->protocolo }}</td>
                            <td>{{ $inscricao->nome_completo }}</td>
                            <td>{{ $inscricao->email }}</td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
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

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="data" value="{{ $date }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <label for="per_page_bottom" class="text-sm text-slate-600">Itens por página</label>
                <select id="per_page_bottom" name="per_page" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="this.form.submit()">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((string) $perPage === (string) $option)>
                            {{ $option === 'all' ? 'Todos' : $option }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="flex items-center gap-2">
                @if ($inscricoes->previousPageUrl())
                    <a href="{{ $inscricoes->previousPageUrl() }}" class="btn-muted">Anterior</a>
                @else
                    <span class="btn-muted cursor-not-allowed opacity-50">Anterior</span>
                @endif

                <span class="text-sm text-slate-600">
                    Página {{ $inscricoes->currentPage() }} de {{ max(1, $inscricoes->lastPage()) }}
                </span>

                @if ($inscricoes->nextPageUrl())
                    <a href="{{ $inscricoes->nextPageUrl() }}" class="btn-muted">Próximo</a>
                @else
                    <span class="btn-muted cursor-not-allowed opacity-50">Próximo</span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
