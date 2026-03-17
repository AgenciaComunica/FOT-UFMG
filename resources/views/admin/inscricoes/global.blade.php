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
                    <option value="RECEBIDA" @selected($status === 'RECEBIDA')>Em Homologação</option>
                    <option value="HOMOLOGADA" @selected($status === 'HOMOLOGADA')>Homologada</option>
                    <option value="PRE_APROVADA" @selected($status === 'PRE_APROVADA')>Classificada</option>
                    <option value="PRE_INDEFERIDA" @selected($status === 'PRE_INDEFERIDA')>Excedente</option>
                    <option value="INDEFERIDA" @selected($status === 'INDEFERIDA')>Não homologada</option>
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
                @click="bulkModalOpen = true; bulkAction = 'status'; bulkStatus = 'HOMOLOGADA'"
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
                            $statusClass = \App\Models\Inscricao::statusBadgeClass($inscricao->status);
                            $statusLabel = \App\Models\Inscricao::statusLabel($inscricao->status);
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox"
                                       class="rounded border-slate-300 text-blue-600"
                                       value="{{ $inscricao->id }}"
                                       @change="toggleOne({{ $inscricao->id }}, $event.target.checked)"
                                       :checked="selectedIds.includes({{ $inscricao->id }})">
                            </td>
                            <td class="font-semibold">
                                <a href="{{ route('admin.inscricoes.show', $inscricao) }}" class="text-slate-700 hover:text-blue-700 hover:underline">
                                    {{ $inscricao->protocolo }}
                                </a>
                            </td>
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
                                       class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                        Ver
                                    </a>
                                    <form method="POST" id="delete-inscricao-{{ $inscricao->id }}" action="{{ route('admin.inscricoes.destroy', $inscricao) }}" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                        @click="singleDeleteFormId = 'delete-inscricao-{{ $inscricao->id }}'; singleDeleteLabel = '{{ $inscricao->protocolo }}'; confirmSingleDeleteOpen = true"
                                    >
                                        Excluir
                                    </button>
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
            <div class="w-full max-w-3xl rounded-xl bg-white p-4 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-900">Ação para vários</h3>
                    <form x-show="bulkAction === 'status'" method="POST" action="{{ route('admin.inscricoes.status.bulk') }}" class="mt-4 space-y-3">
                        @csrf
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="edital_id" value="{{ $editalId }}">
                    <input type="hidden" name="status" :value="bulkStatus">
                    <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                    <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
                    <template x-for="id in selectedIds" :key="`sel-${id}`">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Controle de Inscrição</p>
                            <div class="flex items-center justify-center gap-2 py-1">
                                <button type="button" class="btn-success whitespace-nowrap" @click="bulkStatus='HOMOLOGADA'">Homologar</button>
                                <button type="button" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700 whitespace-nowrap" @click="bulkStatus='PRE_APROVADA'">Classificar</button>
                                <button type="button" class="rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600 whitespace-nowrap" @click="bulkStatus='PRE_INDEFERIDA'">Excedente</button>
                                <button type="button" class="btn-muted whitespace-nowrap" @click="bulkStatus='HOMOLOGADA'">Voltar à Análise</button>
                                <button type="button" class="btn-danger whitespace-nowrap" @click="bulkStatus='INDEFERIDA'">Não homologar</button>
                            </div>
                        </div>
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 md:col-span-1">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-red-700">Exclusão de Inscrição</p>
                            <div class="flex items-center justify-center gap-2 py-1">
                                <button type="button" class="btn-danger whitespace-nowrap" @click="bulkAction='delete'">Excluir</button>
                            </div>
                        </div>
                    </div>
                    <div x-show="bulkStatus === 'INDEFERIDA'">
                        <x-input-label for="bulk_indeferimento_motivo" value="Motivo da não homologação (obrigatório)" />
                        <textarea id="bulk_indeferimento_motivo" name="indeferimento_motivo" rows="3" class="input-base"></textarea>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700" x-text="['HOMOLOGADA', 'INDEFERIDA', 'PRE_APROVADA', 'PRE_INDEFERIDA'].includes(bulkStatus) ? 'Ao confirmar, o sistema enviará e-mail aos candidatos selecionados.' : 'Ao confirmar, o sistema atualizará o status das inscrições selecionadas.'">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-muted" @click="bulkModalOpen=false">Cancelar</button>
                        <button type="submit" class="btn-primary">Aplicar</button>
                    </div>
                </form>

                <form x-show="bulkAction === 'delete'" method="POST" action="{{ route('admin.inscricoes.destroy.bulk') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="edital_id" value="{{ $editalId }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                    <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
                    <template x-for="id in selectedIds" :key="`del-${id}`">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>

                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        Você está prestes a excluir as inscrições selecionadas. Esta ação é irreversível.
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-muted" @click="bulkAction='status'">Voltar</button>
                        <button type="submit" class="btn-danger">Excluir selecionadas</button>
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

        <div x-show="confirmSingleDeleteOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display:none;" @click.self="confirmSingleDeleteOpen=false">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Confirmar exclusão</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Deseja realmente excluir a inscrição
                    <span class="font-semibold text-slate-800" x-text="singleDeleteLabel"></span>?
                </p>
                <p class="mt-1 text-xs text-red-600">Esta ação é irreversível.</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="btn-muted" @click="confirmSingleDeleteOpen=false">Cancelar</button>
                    <button type="button" class="btn-danger" @click="submitSingleDelete()">Excluir</button>
                </div>
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
                bulkAction: 'status',
                bulkStatus: 'HOMOLOGADA',
                confirmSingleDeleteOpen: false,
                singleDeleteFormId: '',
                singleDeleteLabel: '',
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
                submitSingleDelete() {
                    if (!this.singleDeleteFormId) return;
                    const form = document.getElementById(this.singleDeleteFormId);
                    if (form) form.submit();
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
