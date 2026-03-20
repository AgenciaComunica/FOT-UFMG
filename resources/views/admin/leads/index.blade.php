<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Leads</h2>
            <p class="text-sm text-slate-500">Contatos interessados em receber novidades sobre abertura e encerramento de editais.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="leadsPage(@js($dateStart), @js($dateEnd))">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" class="panel-card grid gap-3 md:grid-cols-[minmax(0,1.35fr)_minmax(260px,0.9fr)_auto] md:items-end" x-ref="filterForm">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <input type="hidden" name="data_inicio" x-model="startDate">
            <input type="hidden" name="data_fim" x-model="endDate">
            <div class="min-w-0">
                <x-input-label for="q" value="Pesquisar lead" />
                <x-text-input
                    id="q"
                    name="q"
                    type="text"
                    class="input-base"
                    data-preserve-focus="1"
                    :value="$q"
                    placeholder="Nome ou e-mail"
                    @input="clearTimeout(timer); timer = setTimeout(() => $refs.filterForm.submit(), 350)"
                />
            </div>
            <div class="min-w-0">
                <x-input-label value="Último cadastro" />
                <input type="text" x-ref="range" class="input-base" readonly>
            </div>
            <div class="flex flex-wrap gap-2 md:flex-nowrap md:justify-end">
                @if ($q !== '' || $dateStart || $dateEnd)
                    <a href="{{ route('admin.leads.index') }}" class="btn-muted">Limpar</a>
                @endif
                <button type="button" class="inline-flex h-[42px] items-center gap-1.5 whitespace-nowrap rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" @click="openImportModal = true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6 15a3 3 0 010-6h.26A5 5 0 1115 10h.5a2.5 2.5 0 010 5H6z" />
                        <path d="M10 8a1 1 0 011 1v3h1.586L10 14.586 7.414 12H9V9a1 1 0 011-1z" />
                    </svg>
                    Importar Leads
                </button>
                <a href="{{ route('admin.leads.create') }}" class="inline-flex h-[42px] items-center whitespace-nowrap rounded-md bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">+ Novo Lead</a>
            </div>
        </form>

        <div class="flex justify-end gap-2">
            <button
                type="button"
                class="inline-flex h-[42px] items-center rounded-md px-4 text-sm font-semibold transition"
                :class="selectedIds.length > 0
                    ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                    : 'cursor-not-allowed bg-slate-200 text-slate-400'"
                :disabled="selectedIds.length === 0"
                @click="exportModalOpen = true"
            >
                Exportar Leads (CSV/XLS)
            </button>
            <button
                type="button"
                class="inline-flex h-[42px] items-center rounded-md px-4 text-sm font-semibold transition"
                :class="selectedIds.length > 0
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'cursor-not-allowed bg-slate-200 text-slate-400'"
                :disabled="selectedIds.length === 0"
                @click="sendModalOpen = true"
            >
                Disparar aviso
            </button>
        </div>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input type="checkbox" class="rounded border-slate-300 text-blue-600" @change="toggleAll($event.target.checked)">
                            </label>
                        </th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Último cadastro</th>
                        <th>Último disparo</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>
                                <input type="checkbox"
                                       class="rounded border-slate-300 text-blue-600"
                                       @change="toggleOne({{ $lead->id }}, $event.target.checked)"
                                       :checked="selectedIds.includes({{ $lead->id }})">
                            </td>
                            <td class="font-semibold text-slate-700">{{ $lead->nome }}</td>
                            <td>{{ $lead->email }}</td>
                            <td>{{ optional($lead->updated_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td>{{ optional($lead->last_notified_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"
                                        @click="selectedIds = [{{ $lead->id }}]; sendModalOpen = true"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.94 6.34a2 2 0 012.12-1.72l9.84.82a2 2 0 011.6 3.05l-5.2 8.67a2 2 0 01-3.58-.28l-1.18-3.17-3.18-1.18a2 2 0 01-.42-3.54l8.9-5.34-7.86 6.82 1.85.68a2 2 0 011.18 1.18l.68 1.85 4.82-8.03-9.57-.8z" />
                                        </svg>
                                        Disparar
                                    </button>
                                    <a href="{{ route('admin.leads.edit', $lead) }}" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.9 9.9a1 1 0 01-.46.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.46l9.9-9.9a2 2 0 012.828 0z" />
                                        </svg>
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" class="m-0" onsubmit="return confirm('Deseja excluir este lead?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex h-9 items-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.5 4H16a1 1 0 110 2h-.617l-.666 9.327A2 2 0 0112.722 17H7.278a2 2 0 01-1.995-1.673L4.617 6H4a1 1 0 010-2h3.5l.757-.901z" clip-rule="evenodd" />
                                            </svg>
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-slate-500">Nenhum lead encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="sendModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display:none;" @click.self="sendModalOpen = false">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Disparar aviso para leads</h3>
                <form method="POST" action="{{ route('admin.leads.send-manual') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="data_inicio" value="{{ $dateStart }}">
                    <input type="hidden" name="data_fim" value="{{ $dateEnd }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <template x-for="id in selectedIds" :key="`lead-${id}`">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <div>
                        <x-input-label for="edital_id" value="Edital" />
                        <select id="edital_id" name="edital_id" class="input-base mt-1" required>
                            <option value="">Selecione</option>
                            @foreach ($editais as $edital)
                                <option value="{{ $edital->id }}">{{ $edital->titulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="tipo_aviso" value="Tipo de aviso" />
                        <select id="tipo_aviso" name="tipo_aviso" class="input-base mt-1" required>
                            <option value="aberto">Próximo edital aberto</option>
                            <option value="encerrando">Edital próximo de encerrar</option>
                        </select>
                    </div>
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">
                        O disparo será enviado manualmente apenas para os leads selecionados.
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-muted" @click="sendModalOpen = false">Cancelar</button>
                        <button type="submit" class="btn-primary">Enviar aviso</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="exportModalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display:none;" @click.self="exportModalOpen = false">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Exportar leads selecionados</h3>
                <p class="mt-2 text-sm text-slate-600">
                    <span class="font-semibold text-slate-900" x-text="selectedIds.length"></span>
                    lead(s) selecionado(s) para exportação.
                </p>
                <form method="POST" action="{{ route('admin.leads.export') }}" class="mt-4 space-y-3">
                    @csrf
                    <template x-for="id in selectedIds" :key="`export-lead-${id}`">
                        <input type="hidden" name="selected_ids[]" :value="id">
                    </template>
                    <div>
                        <x-input-label for="formato_exportacao" value="Formato do arquivo" />
                        <select id="formato_exportacao" name="formato" class="input-base mt-1" required>
                            <option value="csv">CSV</option>
                            <option value="xls">Excel (.xls)</option>
                        </select>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                        A exportação incluirá apenas os leads selecionados na tabela.
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-muted" @click="exportModalOpen = false">Cancelar</button>
                        <button type="submit" class="btn-primary">Exportar</button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-show="openImportModal"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            style="display: none;"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Importar leads</h3>
                        <p class="mt-1 text-sm text-slate-600">Envie um arquivo CSV ou XLSX com colunas de nome e email.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="openImportModal = false">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.leads.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="arquivo" value="Arquivo (CSV ou XLSX)" />
                        <input id="arquivo" name="arquivo" type="file" accept=".csv,.txt,.xlsx" class="input-base mt-1">
                        <x-input-error :messages="$errors->get('arquivo')" class="mt-2" />
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        Formato esperado:
                        <br>Colunas com cabeçalho `nome` e `email`, ou nome/email nas duas primeiras colunas.
                        <br>
                        <a href="{{ route('admin.leads.template') }}" class="mt-1 inline-flex font-semibold text-blue-600 hover:underline">Baixar modelo Excel (CSV)</a>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-muted" @click="openImportModal = false">Cancelar</button>
                        <button type="submit" class="btn-primary">Importar</button>
                    </div>
                </form>
            </div>
        </div>

        @php($importResult = session('import_result'))
        @if ($importResult)
            <div
                x-show="openImportResultModal"
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
                style="display: none;"
            >
                <div class="w-full max-w-xl rounded-xl bg-white p-5 shadow-xl">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900">Importação realizada</h3>
                        <button type="button" class="text-slate-400 hover:text-slate-600" @click="openImportResultModal = false">✕</button>
                    </div>

                    <p class="mt-3 text-sm text-slate-700">
                        {{ $importResult['importados'] ?? 0 }} leads cadastrados/atualizados,
                        {{ $importResult['falhas_total'] ?? 0 }} leads NÃO foram processados, ver abaixo.
                    </p>

                    @if (!empty($importResult['falhas']))
                        <div class="mt-3 max-h-72 overflow-auto rounded-md border border-red-200 bg-red-50 p-3">
                            <ul class="space-y-1 text-sm text-red-700">
                                @foreach ($importResult['falhas'] as $falha)
                                    <li>Lead da linha {{ $falha['linha'] ?? '?' }} faltou/erro: {{ $falha['motivo'] ?? 'dado inválido' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-4 flex justify-end">
                        <button type="button" class="btn-primary" @click="openImportResultModal = false">Fechar</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="q" value="{{ $q }}">
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
                @if ($leads->previousPageUrl())
                    <a href="{{ $leads->previousPageUrl() }}" class="btn-muted">Anterior</a>
                @else
                    <span class="btn-muted cursor-not-allowed opacity-50">Anterior</span>
                @endif

                <span class="text-sm text-slate-600">
                    Página {{ $leads->currentPage() }} de {{ max(1, $leads->lastPage()) }}
                </span>

                @if ($leads->nextPageUrl())
                    <a href="{{ $leads->nextPageUrl() }}" class="btn-muted">Próximo</a>
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
        function leadsPage(initialStart, initialEnd) {
            return {
                timer: null,
                sendModalOpen: false,
                exportModalOpen: false,
                openImportModal: {{ $errors->has('arquivo') ? 'true' : 'false' }},
                openImportResultModal: {{ session()->has('import_result') ? 'true' : 'false' }},
                selectedIds: [],
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
                    const ids = @js($leads->pluck('id')->values());
                    this.selectedIds = checked ? [...ids] : [];
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
