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

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('docente.inscricoes.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'pendente'])) }}"
               class="rounded-lg px-3 py-2 text-sm font-semibold {{ $tab === 'pendente' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                Avaliação Pendente
            </a>
            <a href="{{ route('docente.inscricoes.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'avaliado'])) }}"
               class="rounded-lg px-3 py-2 text-sm font-semibold {{ $tab === 'avaliado' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                Avaliado
            </a>
            @if ($hasAprovadorAny)
                <a href="{{ route('docente.inscricoes.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'aprovacao'])) }}"
                   class="rounded-lg px-3 py-2 text-sm font-semibold {{ $tab === 'aprovacao' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                    Aprovação
                </a>
            @endif
        </div>

        <form method="GET" class="panel-card grid gap-3 md:grid-cols-9 md:items-end" x-ref="filterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
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
            @if ($tab === 'aprovacao')
                <div>
                    <x-input-label for="final_status" value="Status" />
                    <select id="final_status" name="final_status" class="input-base" @change="$refs.filterForm.submit()">
                        <option value="" @selected($finalStatus === '')>Todos</option>
                        <option value="HOMOLOGADA" @selected($finalStatus === 'HOMOLOGADA')>Homologada</option>
                        <option value="PRE_APROVADA" @selected($finalStatus === 'PRE_APROVADA')>Classificada</option>
                        <option value="PRE_INDEFERIDA" @selected($finalStatus === 'PRE_INDEFERIDA')>Excedente</option>
                        <option value="INDEFERIDA" @selected($finalStatus === 'INDEFERIDA')>Não homologada</option>
                    </select>
                </div>
            @endif
            <div class="flex gap-2">
                @if ($search !== '' || $editalId > 0 || $dateStart || $dateEnd)
                    <a href="{{ route('docente.inscricoes.index', ['tab' => $tab]) }}" class="btn-muted">Limpar</a>
                @endif
            </div>
        </form>

        @if ($tab === 'aprovacao')
            <div class="flex justify-end">
                <button type="button" class="btn-primary" x-show="selectedIds.length > 0" @click="bulkModalOpen = true; bulkStatus = 'HOMOLOGADA'">Aplicar em vários</button>
            </div>
        @endif

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        @if ($tab === 'aprovacao')
                            <th>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" class="rounded border-slate-300 text-blue-600" @change="toggleAll($event.target.checked)">
                                </label>
                            </th>
                        @endif
                        <th>Protocolo</th>
                        <th>Candidato</th>
                        <th>Edital</th>
                        @if ($tab === 'aprovacao')
                            <th>Status geral</th>
                            <th>Status final</th>
                            <th>Enviada em</th>
                        @else
                            <th>Status da Avaliação</th>
                            <th>Nota</th>
                            <th>Data última avaliação</th>
                        @endif
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inscricoes as $inscricao)
                        @php
                            $avaliacaoAtual = $inscricao->avaliacoes->first();
                            $statusAvaliacao = $avaliacaoAtual && $avaliacaoAtual->nota !== null ? 'AVALIADO' : 'PENDENTE';
                            $badge = $statusAvaliacao === 'AVALIADO' ? 'status-homologada' : 'bg-blue-100 text-blue-700';
                            $statusFinalClass = \App\Models\Inscricao::statusBadgeClass($inscricao->status);
                            $statusFinalLabel = \App\Models\Inscricao::statusLabel($inscricao->status);
                        @endphp
                        <tr>
                            @if ($tab === 'aprovacao')
                                <td>
                                    <input type="checkbox"
                                           class="rounded border-slate-300 text-blue-600"
                                           @change="toggleOne({{ $inscricao->id }}, $event.target.checked)"
                                           :checked="selectedIds.includes({{ $inscricao->id }})">
                                </td>
                            @endif
                            <td class="font-semibold text-slate-700">{{ $inscricao->protocolo }}</td>
                            <td>{{ $inscricao->nome_completo }}</td>
                            <td>{{ $inscricao->edital?->titulo }}</td>
                            @if ($tab === 'aprovacao')
                                @php
                                    $totalBanca = $inscricao->edital?->docentesBanca?->count() ?? 0;
                                    $totalAvaliadas = $inscricao->avaliacoes->whereNotNull('nota')->count();
                                    $statusGeralClass = $totalAvaliadas === 0
                                        ? 'status-indeferida'
                                        : ($totalAvaliadas >= $totalBanca && $totalBanca > 0
                                            ? 'status-homologada'
                                            : 'status-recebida');
                                    $statusGeralLabel = $totalAvaliadas === 0
                                        ? 'Pendente'
                                        : ($totalAvaliadas >= $totalBanca && $totalBanca > 0
                                            ? 'Concluída'
                                            : 'Em análise');
                                @endphp
                                <td><span class="status-badge {{ $statusGeralClass }}">{{ $statusGeralLabel }}</span></td>
                                <td><span class="status-badge {{ $statusFinalClass }}">{{ $statusFinalLabel }}</span></td>
                                <td>{{ optional($inscricao->submitted_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            @else
                                <td><span class="status-badge {{ $badge }}">{{ $statusAvaliacao === 'AVALIADO' ? 'Avaliado' : 'Avaliação Pendente' }}</span></td>
                                <td>{{ $avaliacaoAtual && $avaliacaoAtual->nota !== null ? number_format((float) $avaliacaoAtual->nota, 2, ',', '.') : '-' }}</td>
                                <td>{{ $avaliacaoAtual && $avaliacaoAtual->nota !== null && $avaliacaoAtual->updated_at ? $avaliacaoAtual->updated_at->format('d/m/Y H:i') : '-' }}</td>
                            @endif
                            <td>
                                <a href="{{ route('docente.inscricoes.show', $inscricao) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                    {{ $tab === 'aprovacao' ? 'Ver detalhes' : 'Avaliar' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab === 'aprovacao' ? '8' : '7' }}" class="text-slate-500">Nenhuma inscrição encontrada para os filtros atuais.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tab === 'aprovacao')
            <div x-show="bulkModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display:none;" @click.self="bulkModalOpen=false">
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-900">Ação para vários</h3>
                    <form method="POST" action="{{ route('docente.inscricoes.status.bulk') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="q" value="{{ $search }}">
                        <input type="hidden" name="edital_id" value="{{ $editalId }}">
                        <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                        <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
                        <input type="hidden" name="final_status" value="{{ $finalStatus }}">
                        <input type="hidden" name="status" :value="bulkStatus">
                        <template x-for="id in selectedIds" :key="`sel-doc-${id}`">
                            <input type="hidden" name="selected_ids[]" :value="id">
                        </template>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn-muted" @click="bulkStatus='HOMOLOGADA'">Voltar à Análise</button>
                            <button type="button" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700" @click="bulkStatus='PRE_APROVADA'">Classificar</button>
                            <button type="button" class="rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600" @click="bulkStatus='PRE_INDEFERIDA'">Excedente</button>
                            <button type="button" class="btn-danger" @click="bulkStatus='INDEFERIDA'">Não homologar</button>
                        </div>
                        <div x-show="bulkStatus === 'INDEFERIDA'">
                            <x-input-label for="bulk_docente_indeferimento_motivo" value="Motivo da não homologação (obrigatório)" />
                            <textarea id="bulk_docente_indeferimento_motivo" name="indeferimento_motivo" rows="3" class="input-base"></textarea>
                        </div>
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700" x-text="['INDEFERIDA', 'PRE_APROVADA', 'PRE_INDEFERIDA'].includes(bulkStatus) ? 'Ao confirmar, o sistema enviará e-mail aos candidatos selecionados.' : 'Ao confirmar, o sistema atualizará o status das inscrições selecionadas.'">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="btn-muted" @click="bulkModalOpen=false">Cancelar</button>
                            <button type="submit" class="btn-primary">Aplicar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <input type="hidden" name="edital_id" value="{{ $editalId }}">
                <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
                <input type="hidden" name="final_status" value="{{ $finalStatus }}">
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
                selectedIds: [],
                bulkModalOpen: false,
                bulkStatus: 'HOMOLOGADA',
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
