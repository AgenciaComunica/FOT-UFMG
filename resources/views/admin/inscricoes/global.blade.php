<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Inscrições</h2>
            <p class="text-sm text-slate-500">Todas as inscrições realizadas no sistema.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="rangeInscricaoFilter(@js($dateStart), @js($dateEnd))">
        <form method="GET" x-show="showFilters" x-transition class="panel-card grid gap-3 md:grid-cols-6 md:items-end" x-ref="filterForm">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <input type="hidden" name="data_inicio" x-model="startDate">
            <input type="hidden" name="data_fim" x-model="endDate">
            <div class="md:col-span-2">
                <x-input-label for="q" value="Nome ou protocolo" />
                <x-text-input
                    id="q"
                    name="q"
                    type="text"
                    class="input-base"
                    data-preserve-focus="1"
                    :value="$search"
                    placeholder="Nome, protocolo, email ou CPF"
                    @input="clearTimeout(timer); timer = setTimeout(() => $refs.filterForm.submit(), 350)"
                />
            </div>
            <div>
                <x-input-label for="edital_id" value="Edital" />
                <select id="edital_id" name="edital_id" class="input-base" @change="$refs.filterForm.submit()">
                    <option value="0">Todos</option>
                    @foreach ($editais as $edital)
                        <option value="{{ $edital->id }}" @selected($editalId === $edital->id)>{{ $edital->titulo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="input-base" @change="$refs.filterForm.submit()">
                    <option value="">Todos</option>
                    <option value="RECEBIDA" @selected($status === 'RECEBIDA')>Em Análise</option>
                    <option value="PRE_APROVADA" @selected($status === 'PRE_APROVADA')>Pré-Aprovado</option>
                    <option value="PRE_INDEFERIDA" @selected($status === 'PRE_INDEFERIDA')>Pré-Indeferido</option>
                    <option value="HOMOLOGADA" @selected($status === 'HOMOLOGADA')>Homologada</option>
                    <option value="INDEFERIDA" @selected($status === 'INDEFERIDA')>Indeferida</option>
                </select>
            </div>
            <div>
                <x-input-label value="Período envio" />
                <input type="text" x-ref="range" class="input-base" readonly>
            </div>
            @if ($filtroAlterado)
                <div class="flex gap-2">
                    <a href="{{ route('admin.inscricoes.index') }}" class="btn-muted">Limpar</a>
                </div>
            @endif
            <div class="flex items-end gap-2">
                <a href="{{ route('admin.inscricoes.export', request()->query()) }}" class="inline-flex h-[42px] items-center rounded-md bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">
                    Exportar
                </a>
            </div>
        </form>

        <div class="flex justify-end">
            <button
                type="button"
                class="inline-flex h-[42px] items-center rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700"
                x-show="selectedIds.length > 0"
                @click="bulkModalOpen = true"
            >
                Aplicar em vários
            </button>
        </div>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="rounded border-slate-300 text-blue-600" @change="toggleAll($event.target.checked)">
                                </label>
                            </th>
                        <th>Protocolo</th>
                        <th>Nome</th>
                        <th>Edital</th>
                        <th>Status</th>
                        <th>E-mail verificado</th>
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
                                'PRE_APROVADA' => 'bg-cyan-100 text-cyan-700',
                                'PRE_INDEFERIDA' => 'bg-orange-100 text-orange-700',
                                default => 'status-recebida',
                            };
                            $statusLabel = match($inscricao->status) {
                                'HOMOLOGADA' => 'Homologada',
                                'INDEFERIDA' => 'Indeferida',
                                'PRE_APROVADA' => 'Pré-Aprovado',
                                'PRE_INDEFERIDA' => 'Pré-Indeferido',
                                default => 'Em Análise',
                            };
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox"
                                       class="rounded border-slate-300 text-blue-600"
                                       value="{{ $inscricao->id }}"
                                       @change="toggleOne({{ $inscricao->id }}, $event.target.checked)"
                                       :checked="selectedIds.includes({{ $inscricao->id }})">
                            </td>
                            <td class="font-semibold text-slate-700">{{ $inscricao->protocolo }}</td>
                            <td>{{ $inscricao->nome_completo }}</td>
                            <td>{{ $inscricao->edital?->titulo ?? '-' }}</td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                @if ($inscricao->email_verified_at)
                                    <span class="status-badge status-homologada">Verificado</span>
                                @else
                                    <span class="status-badge status-indeferida">Não verificado</span>
                                @endif
                            </td>
                            <td>{{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.inscricoes.show', $inscricao) }}"
                                       class="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 3C5 3 1.73 7.11 1 9c.73 1.89 4 6 9 6s8.27-4.11 9-6c-.73-1.89-4-6-9-6zm0 10a4 4 0 110-8 4 4 0 010 8z" />
                                        </svg>
                                        Ver detalhes
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-slate-500">Nenhuma inscrição encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="bulkModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display:none;" @click.self="bulkModalOpen=false">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-900">Ação para vários</h3>
                    <form method="POST" action="{{ route('admin.inscricoes.status.bulk') }}" class="mt-4 space-y-3">
                        @csrf
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="edital_id" value="{{ $editalId }}">
                    <input type="hidden" name="status" :value="bulkStatus">
                    <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                    <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
                    <template x-for="id in selectedIds" :key="`sel-${id}`">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn-muted" @click="bulkStatus='RECEBIDA'">Em Análise</button>
                        <button type="button" class="btn-success" @click="bulkStatus='HOMOLOGADA'">Homologar</button>
                        <button type="button" class="btn-danger" @click="bulkStatus='INDEFERIDA'">Indeferir</button>
                    </div>
                    <div x-show="bulkStatus === 'INDEFERIDA'">
                        <x-input-label for="bulk_indeferimento_motivo" value="Motivo do indeferimento (obrigatório)" />
                        <textarea id="bulk_indeferimento_motivo" name="indeferimento_motivo" rows="3" class="input-base"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-muted" @click="bulkModalOpen=false">Cancelar</button>
                        <button type="submit" class="btn-primary">Aplicar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="edital_id" value="{{ $editalId }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        function rangeInscricaoFilter(initialStart, initialEnd) {
            return {
                showFilters: true,
                timer: null,
                selectedIds: [],
                bulkModalOpen: false,
                bulkStatus: 'RECEBIDA',
                startDate: initialStart || '',
                endDate: initialEnd || '',
                toggleOne(id, checked) {
                    if (checked) {
                        if (!this.selectedIds.includes(id)) this.selectedIds.push(id);
                        return;
                    }
                    this.selectedIds = this.selectedIds.filter((item) => item !== id);
                },
                toggleAll(checked) {
                    const ids = @js($inscricoes->pluck('id')->values());
                    if (checked) {
                        this.selectedIds = [...ids];
                        return;
                    }
                    this.selectedIds = [];
                },
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
