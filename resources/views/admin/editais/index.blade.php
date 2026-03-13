<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Painel</h2>
            <p class="text-sm text-slate-500">Visão geral dos editais e comportamento das inscrições.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="deleteEditalModal()">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->has('edital'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('edital') }}</div>
        @endif

        <section class="panel-card space-y-4">
            <form
                method="GET"
                class="grid gap-3 border-b border-slate-200 pb-4 md:grid-cols-2 md:items-end"
                x-ref="graficoForm"
            >
                <input type="hidden" name="q" value="{{ $q }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="cards_inicio" value="{{ $cardsInicio }}">
                <input type="hidden" name="cards_fim" value="{{ $cardsFim }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div>
                    <x-input-label for="grafico_edital_id" value="Edital (gráficos)" />
                    <select id="grafico_edital_id" name="grafico_edital_id" class="input-base" @change="$refs.graficoForm.submit()">
                        @foreach ($graficoEditais as $editalGrafico)
                            <option value="{{ $editalGrafico->id }}" @selected($graficoEditalId === $editalGrafico->id)>{{ $editalGrafico->titulo }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($graficoFiltroAlterado)
                    <div class="flex items-end">
                        <a href="{{ route('admin.painel') }}" class="btn-muted">Limpar filtro</a>
                    </div>
                @endif
            </form>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-2">
                    <div class="mb-3">
                        <h3 class="text-base font-semibold text-slate-900">
                            Curva de inscrições ({{ optional($graficoEditais->firstWhere('id', $graficoEditalId))->titulo ?? 'Edital selecionado' }})
                        </h3>
                        <p class="text-xs text-slate-500">
                            A curva considera o período selecionado no filtro acima
                            (escala: {{ $graficoGranularidade === 'ano' ? 'anual' : ($graficoGranularidade === 'mes' ? 'mensal' : 'diária') }}).
                        </p>
                    </div>
                    <div id="chart-inscricoes-tempo" class="min-h-[280px]"></div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-4 lg:col-span-1">
                    <div class="mb-3">
                        <h3 class="text-base font-semibold text-slate-900">Proporção por status de inscrição</h3>
                    </div>
                    <div id="chart-inscricoes-status" class="min-h-[280px]"></div>
                </section>
            </div>
        </section>

        <section x-data="rangeCardsFilter(@js($cardsInicio), @js($cardsFim))" class="panel-card space-y-4">
            <form method="GET" class="space-y-3 border-b border-slate-200 pb-4" x-ref="filterForm">
                <input type="hidden" name="grafico_edital_id" value="{{ $graficoEditalId }}">
                <input type="hidden" name="cards_inicio" x-model="startDate">
                <input type="hidden" name="cards_fim" x-model="endDate">

                <div class="md:flex md:items-end md:gap-3">
                    <div class="grid flex-1 gap-3 md:grid-cols-3">
                        <div>
                            <x-input-label for="q" value="Pesquisar edital" />
                            <x-text-input
                                id="q"
                                name="q"
                                type="text"
                                class="input-base"
                                data-preserve-focus="1"
                                :value="$q"
                                placeholder="Título ou descrição"
                                @input="clearTimeout(timer); timer = setTimeout(() => $refs.filterForm.submit(), 350)"
                            />
                        </div>
                        <div>
                            <x-input-label value="Filtro período" />
                            <input type="text" x-ref="range" class="input-base" readonly>
                        </div>
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="input-base" @change="$refs.filterForm.submit()">
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $statusOpcao)
                                    <option value="{{ $statusOpcao }}" @selected($status === $statusOpcao)>{{ $statusOpcao }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 flex items-end justify-end md:mt-0 md:pb-[2px]">
                        @if ($cardsFiltroAlterado)
                            <a href="{{ route('admin.painel', ['grafico_edital_id' => $graficoEditalId, 'per_page' => $perPage]) }}" class="btn-muted whitespace-nowrap">Limpar filtro</a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <a
                    href="{{ route('admin.editais.create') }}"
                    class="group flex min-h-[280px] flex-col items-center justify-center rounded-xl border border-dashed border-blue-300 bg-blue-50/60 p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:bg-blue-50"
                >
                    <span class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-sm transition group-hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <p class="text-base font-semibold text-blue-800">Adicionar Edital</p>
                    <p class="mt-1 text-sm text-blue-700">Clique para criar um novo edital.</p>
                </a>

                @if ($editais->isEmpty())
                    <div class="flex min-h-[280px] items-center justify-center rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-600 shadow-sm sm:col-span-1 xl:col-span-2">
                        Nenhum edital encontrado com os filtros atuais.
                    </div>
                @endif

                @if ($editais->isNotEmpty())
                    @foreach ($editais as $edital)
                        @php
                            $badgeClass = match($edital->status) {
                                'ABERTO' => 'status-homologada',
                                'RASCUNHO' => 'status-indeferida',
                                'ENCERRADO' => 'bg-slate-200 text-slate-700',
                                default => 'status-recebida',
                            };
                        @endphp
                        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-base font-bold text-slate-900">{{ $edital->titulo }}</h3>
                                <span class="status-badge {{ $badgeClass }}">{{ $edital->status }}</span>
                            </div>

                            <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($edital->descricao ?: 'Sem descrição', 110) }}</p>

                            <dl class="mt-4 space-y-1 text-xs text-slate-500">
                                <div class="flex justify-between gap-3">
                                    <dt>Início</dt>
                                    <dd class="font-semibold text-slate-700">{{ $edital->periodo_inscricao_inicio->format('d/m/Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt>Fim</dt>
                                    <dd class="font-semibold text-slate-700">{{ $edital->periodo_inscricao_fim->format('d/m/Y H:i') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-3">
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Banca de Docentes</p>
                                @if ($edital->docentesBanca->isEmpty())
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-600">Sem docentes na banca</span>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($edital->docentesBanca as $docente)
                                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-[11px] text-blue-700">{{ $docente->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3">
                                <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Documentos exigidos</p>
                                @if ($edital->documentosRequeridos->isEmpty())
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-600">Sem documentos exigidos</span>
                                @else
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($edital->documentosRequeridos as $doc)
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">{{ $doc->tipo }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('admin.editais.publicacao', $edital) }}" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                @csrf
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Publicado</p>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            name="publicado"
                                            value="1"
                                            class="peer sr-only"
                                            @checked($edital->publicado)
                                            onchange="this.form.submit()"
                                        >
                                        <div class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></div>
                                        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                    </label>
                                </div>
                            </form>

                            <div class="mt-5 flex items-center gap-2 text-sm">
                                <a
                                    href="{{ route('admin.inscricoes.index', ['edital_id' => $edital->id]) }}"
                                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-blue-700 transition hover:bg-blue-100"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 5a2 2 0 012-2h6.586a1 1 0 01.707.293l1.414 1.414H16a2 2 0 012 2v1H2V5z" />
                                        <path d="M2 9h16v6a2 2 0 01-2 2H4a2 2 0 01-2-2V9z" />
                                    </svg>
                                    Inscrições
                                </a>
                                <a
                                    href="{{ route('admin.editais.edit', $edital) }}"
                                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700 transition hover:bg-slate-100"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M17.414 2.586a2 2 0 010 2.828l-9.9 9.9a1 1 0 01-.46.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.46l9.9-9.9a2 2 0 012.828 0z" />
                                    </svg>
                                    Editar
                                </a>
                                <form method="POST" id="delete-edital-{{ $edital->id }}" action="{{ route('admin.editais.destroy', $edital) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="inline-flex items-center gap-2 whitespace-nowrap rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700 transition hover:bg-red-100"
                                        type="button"
                                        @click="openDeleteModal({{ $edital->id }}, @js($edital->titulo), {{ (int) $edital->inscricoes_count }})"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.5 4H16a1 1 0 110 2h-.617l-.666 9.327A2 2 0 0112.722 17H7.278a2 2 0 01-1.995-1.673L4.617 6H4a1 1 0 010-2h3.5l.757-.901zM8 8a1 1 0 012 0v5a1 1 0 11-2 0V8zm4-1a1 1 0 10-2 0v6a1 1 0 102 0V7z" clip-rule="evenodd" />
                                        </svg>
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3">
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="grafico_edital_id" value="{{ $graficoEditalId }}">
                    <input type="hidden" name="cards_inicio" value="{{ $cardsInicio }}">
                    <input type="hidden" name="cards_fim" value="{{ $cardsFim }}">
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
                    @if ($editais->previousPageUrl())
                        <a href="{{ $editais->previousPageUrl() }}" class="btn-muted">Anterior</a>
                    @else
                        <span class="btn-muted cursor-not-allowed opacity-50">Anterior</span>
                    @endif

                    <span class="text-sm text-slate-600">
                        Página {{ $editais->currentPage() }} de {{ max(1, $editais->lastPage()) }}
                    </span>

                    @if ($editais->nextPageUrl())
                        <a href="{{ $editais->nextPageUrl() }}" class="btn-muted">Próximo</a>
                    @else
                        <span class="btn-muted cursor-not-allowed opacity-50">Próximo</span>
                    @endif
                </div>
            </div>
        </section>

        <div
            x-show="open"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            style="display: none;"
        >
            <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                <h4 class="text-base font-semibold text-slate-900">Excluir edital</h4>
                <p class="mt-2 text-sm text-slate-600">Digite o nome do edital para confirmar a exclusão:</p>
                <p
                    x-show="inscricoesCount > 0"
                    x-transition
                    class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
                >
                    Esta ação irá deletar todas as inscrições vinculadas a este edital.
                </p>
                <p class="mt-2 rounded-md bg-slate-100 px-2 py-1 text-sm font-semibold text-slate-800" x-text="expectedName"></p>
                <div class="mt-3">
                    <input
                        type="text"
                        x-model="typedName"
                        class="input-base"
                        placeholder="Digite o nome exato do edital"
                    >
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" @click="closeModal()" class="btn-muted">Cancelar</button>
                    <button
                        type="button"
                        @click="confirmDelete()"
                        :disabled="typedName.trim() !== expectedName.trim()"
                        class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function rangeCardsFilter(initialStart, initialEnd) {
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

        function deleteEditalModal() {
            return {
                open: false,
                editalId: null,
                expectedName: '',
                inscricoesCount: 0,
                typedName: '',
                openDeleteModal(id, titulo, inscricoesCount = 0) {
                    this.open = true;
                    this.editalId = id;
                    this.expectedName = titulo ?? '';
                    this.inscricoesCount = Number(inscricoesCount || 0);
                    this.typedName = '';
                },
                closeModal() {
                    this.open = false;
                    this.editalId = null;
                    this.expectedName = '';
                    this.inscricoesCount = 0;
                    this.typedName = '';
                },
                confirmDelete() {
                    if (this.typedName.trim() !== this.expectedName.trim() || !this.editalId) {
                        return;
                    }

                    const form = document.getElementById(`delete-edital-${this.editalId}`);
                    if (form) {
                        form.submit();
                    }
                },
            };
        }

        (() => {
            const tempoCtx = document.getElementById('chart-inscricoes-tempo');
            const statusCtx = document.getElementById('chart-inscricoes-status');
            if (!tempoCtx || !statusCtx || typeof ApexCharts === 'undefined') {
                return;
            }

            const tempoOptions = {
                chart: {
                    type: 'area',
                    height: 280,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: 'Manrope, sans-serif',
                },
                series: [{
                    name: 'Inscrições',
                    data: @json($graficoTempoData),
                }],
                xaxis: {
                    categories: @json($graficoTempoLabels),
                    labels: { rotate: -35, trim: true },
                    tickPlacement: 'between',
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    decimalsInFloat: 0,
                    labels: {
                        formatter: function (val) { return Math.round(val); },
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.4,
                        opacityFrom: 0.45,
                        opacityTo: 0.06,
                    },
                },
                colors: ['#2565aa'],
                dataLabels: { enabled: false },
                tooltip: {
                    y: {
                        formatter: function (val) { return `${Math.round(val)} inscrição(ões)`; },
                    },
                },
                grid: {
                    borderColor: '#e2e8f0',
                    padding: {
                        left: 8,
                        right: 18,
                    },
                },
            };

            new ApexCharts(tempoCtx, tempoOptions).render();

            const statusOptions = {
                chart: {
                    type: 'pie',
                    height: 280,
                    toolbar: { show: false },
                    fontFamily: 'Manrope, sans-serif',
                },
                labels: @json($graficoStatusLabels),
                series: @json($graficoStatusData),
                colors: ['#16a34a', '#dc2626', '#2563eb'],
                dataLabels: { enabled: true },
                tooltip: {
                    y: {
                        formatter: function (val) { return `${Math.round(val)} inscrição(ões)`; },
                    },
                },
                legend: {
                    position: 'bottom',
                },
            };

            new ApexCharts(statusCtx, statusOptions).render();
        })();
    </script>
</x-app-layout>
