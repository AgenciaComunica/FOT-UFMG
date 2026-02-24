<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Inscrições</h2>
            <p class="text-sm text-slate-500">Todas as inscrições realizadas no sistema.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="{ showFilters: true }">
        <form method="GET" x-show="showFilters" x-transition class="panel-card grid gap-3 md:grid-cols-5 md:items-end">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <div>
                <x-input-label for="edital_id" value="Edital" />
                <select id="edital_id" name="edital_id" class="input-base">
                    <option value="0">Todos</option>
                    @foreach ($editais as $edital)
                        <option value="{{ $edital->id }}" @selected($editalId === $edital->id)>{{ $edital->titulo }}</option>
                    @endforeach
                </select>
            </div>
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
                <x-input-label for="q" value="Nome ou protocolo" />
                <x-text-input id="q" name="q" type="text" class="input-base" :value="$search" placeholder="Nome, protocolo, email ou CPF" />
            </div>
            <div class="flex gap-2">
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('admin.inscricoes.index') }}" class="btn-muted">Limpar</a>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Nome</th>
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
                            <td>{{ $inscricao->nome_completo }}</td>
                            <td>{{ $inscricao->edital?->titulo ?? '-' }}</td>
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

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="edital_id" value="{{ $editalId }}">
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
