<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Inscrições</h2>
            <p class="text-sm text-slate-500">Avalie as inscrições dos editais em que você participa da banca.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="rangeDocenteFilter(@js($dateStart), @js($dateEnd))">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <form method="GET" class="panel-card grid gap-3 md:grid-cols-9 md:items-end" x-ref="filterForm">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <input type="hidden" name="data_inicio" x-model="startDate">
            <input type="hidden" name="data_fim" x-model="endDate">
            <div class="md:col-span-3">
                <x-input-label for="q" value="Nome, protocolo, edital e nota" />
                <x-text-input
                    id="q"
                    name="q"
                    type="text"
                    class="input-base"
                    data-preserve-focus="1"
                    :value="$search"
                    placeholder="Buscar inscrição"
                    @input="clearTimeout(timer); timer = setTimeout(() => $refs.filterForm.submit(), 350)"
                />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="edital_id" value="Edital" />
                <select id="edital_id" name="edital_id" class="input-base" @change="$refs.filterForm.submit()">
                    <option value="0" @selected($editalId === 0)>Todos</option>
                    @foreach ($editais as $edital)
                        <option value="{{ $edital->id }}" @selected($editalId === $edital->id)>{{ $edital->titulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Período de avaliação" />
                <input type="text" x-ref="range" class="input-base" readonly>
            </div>
            <div>
                <x-input-label for="status" value="Status da Avaliação" />
                <select id="status" name="status" class="input-base" @change="$refs.filterForm.submit()">
                    <option value="PENDENTE" @selected($status === 'PENDENTE')>Avaliação Pendente</option>
                    <option value="AVALIADO" @selected($status === 'AVALIADO')>Avaliado</option>
                </select>
            </div>
            <div class="flex gap-2">
                @if ($search !== '' || $status !== 'PENDENTE' || $editalId > 0 || $dateStart || $dateEnd)
                    <a href="{{ route('docente.inscricoes.index') }}" class="btn-muted">Limpar</a>
                @endif
            </div>
        </form>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Candidato</th>
                        <th>Edital</th>
                        <th>Status da Avaliação</th>
                        <th>Nota</th>
                        <th>Data última avaliação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inscricoes as $inscricao)
                        @php
                            $avaliacaoAtual = $inscricao->avaliacoes->first();
                            $statusAvaliacao = $avaliacaoAtual && $avaliacaoAtual->nota !== null ? 'AVALIADO' : 'PENDENTE';
                            $badge = $statusAvaliacao === 'AVALIADO' ? 'status-homologada' : 'bg-blue-100 text-blue-700';
                        @endphp
                        <tr>
                            <td class="font-semibold text-slate-700">{{ $inscricao->protocolo }}</td>
                            <td>{{ $inscricao->nome_completo }}</td>
                            <td>{{ $inscricao->edital?->titulo }}</td>
                            <td><span class="status-badge {{ $badge }}">{{ $statusAvaliacao === 'AVALIADO' ? 'Avaliado' : 'Avaliação Pendente' }}</span></td>
                            <td>{{ $avaliacaoAtual && $avaliacaoAtual->nota !== null ? number_format((float) $avaliacaoAtual->nota, 2, ',', '.') : '-' }}</td>
                            <td>{{ $avaliacaoAtual && $avaliacaoAtual->nota !== null && $avaliacaoAtual->updated_at ? $avaliacaoAtual->updated_at->format('d/m/Y H:i') : '-' }}</td>
                            <td>
                                <a href="{{ route('docente.inscricoes.show', $inscricao) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Avaliar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-slate-500">Nenhuma inscrição encontrada para sua banca.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <input type="hidden" name="edital_id" value="{{ $editalId }}">
                <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        function rangeDocenteFilter(initialStart, initialEnd) {
            return {
                timer: null,
                startDate: initialStart || '',
                endDate: initialEnd || '',
                init() {
                    if (typeof flatpickr === 'undefined') {
                        return;
                    }

                    const defaultDate = [];
                    if (this.startDate) defaultDate.push(this.startDate);
                    if (this.endDate) defaultDate.push(this.endDate);

                    flatpickr(this.$refs.range, {
                        mode: 'range',
                        dateFormat: 'Y-m-d',
                        defaultDate,
                        locale: (flatpickr.l10ns && flatpickr.l10ns.pt) ? flatpickr.l10ns.pt : undefined,
                        onReady: (_, __, instance) => {
                            instance.input.value = this.formatLabel(this.startDate, this.endDate);
                        },
                        onClose: (selectedDates, dateStr, instance) => {
                            if (selectedDates.length === 2) {
                                this.startDate = instance.formatDate(selectedDates[0], 'Y-m-d');
                                this.endDate = instance.formatDate(selectedDates[1], 'Y-m-d');
                                instance.input.value = this.formatLabel(this.startDate, this.endDate);
                                this.$nextTick(() => this.$refs.filterForm.submit());
                            }
                        },
                    });
                },
                formatLabel(start, end) {
                    if (!start || !end) return 'Selecione um período';
                    const [sy, sm, sd] = start.split('-');
                    const [ey, em, ed] = end.split('-');
                    return `${sd}/${sm}/${sy} até ${ed}/${em}/${ey}`;
                },
            };
        }
    </script>
</x-app-layout>
