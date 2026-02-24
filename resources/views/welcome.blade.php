<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('images/Icone-FTO.png') }}">
        <title>Secretaria FOT-UFMG</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <nav class="w-full border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center">
                    <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" class="h-10 w-auto">
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="btn-primary">Área restrita</a>
                    <a href="http://fisioortotraumaufmg.com.br" target="_blank" rel="noopener noreferrer" class="btn-muted">Voltar ao site</a>
                </div>
            </div>
        </nav>

        <main
            class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8"
            x-data="portalPublico(@js($tab), @js($consultaResultados), @js($consultaTermo), @js($dateStart), @js($dateEnd))"
        >

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-900">Editais</h1>
                        <p class="mt-1 text-sm text-slate-600">Consulta de editais e verificação de inscrição.</p>
                    </div>
                </div>

                <div class="mb-5 flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-semibold"
                        :class="mainTab === 'editais' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'"
                        @click="mainTab = 'editais'"
                    >
                        Ver Editais
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-semibold"
                        :class="mainTab === 'verificar' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'"
                        @click="mainTab = 'verificar'"
                    >
                        Verificar Inscrição
                    </button>
                </div>

                <div x-show="mainTab === 'editais'" x-transition class="space-y-4">
                    <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <form method="GET" class="space-y-4" x-ref="filterForm">
                            <input type="hidden" name="tab" value="editais">
                            <input type="hidden" name="data_inicio" x-model="startDate">
                            <input type="hidden" name="data_fim" x-model="endDate">

                            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-sm font-semibold"
                                    :class="editalTab === 'abertos' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'"
                                    @click="editalTab = 'abertos'"
                                >
                                    Editais Abertos ({{ $abertos->count() }})
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-sm font-semibold"
                                    :class="editalTab === 'encerrados' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700'"
                                    @click="editalTab = 'encerrados'"
                                >
                                    Editais Encerrados ({{ $encerrados->count() }})
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-sm font-semibold"
                                    :class="editalTab === 'proximos' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'"
                                    @click="editalTab = 'proximos'"
                                >
                                    Próximos Editais ({{ $proximos->count() }})
                                </button>
                            </div>

                            <div class="grid gap-3 md:grid-cols-4 md:items-end">
                                <div class="md:col-span-2">
                                    <label for="q" class="mb-1 block text-sm font-semibold text-slate-700">Nome do edital</label>
                                    <input
                                        id="q"
                                        name="q"
                                        type="text"
                                        value="{{ $q }}"
                                        data-preserve-focus="1"
                                        placeholder="Título ou descrição"
                                        class="input-base"
                                        @input="clearTimeout(timer); timer = setTimeout(() => $refs.filterForm.submit(), 350)"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-700">Período dos editais</label>
                                    <input type="text" x-ref="range" class="input-base" readonly>
                                </div>
                                <div class="flex gap-2">
                                    @if ($filtroAlterado)
                                        <a href="{{ route('home', ['tab' => 'editais']) }}" class="btn-muted">Limpar</a>
                                    @endif
                                </div>
                            </div>
                        </form>

                        <div x-show="editalTab === 'abertos'" x-transition>
                        @if ($abertos->isEmpty())
                            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Nenhum edital aberto encontrado.</div>
                        @else
                            <div class="space-y-3">
                                @foreach ($abertos as $edital)
                                    <article class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-sm">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-base font-bold text-slate-900">{{ $edital->titulo }}</h3>
                                                <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($edital->descricao ?: 'Sem descrição.', 220) }}</p>
                                                <p class="mt-1 text-xs font-semibold text-slate-700">Inscrições até {{ $edital->periodo_inscricao_fim->format('d/m/Y H:i') }}</p>
                                            </div>
                                            <span class="status-badge status-homologada">Aberto</span>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a href="{{ route('public.inscricao.create', $edital) }}" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Inscrever-se</a>
                                            @if ($edital->hasArquivoEdital())
                                                <a href="{{ route('public.editais.download', $edital) }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver edital</a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                        </div>

                        <div x-show="editalTab === 'encerrados'" x-transition>
                        @if ($encerrados->isEmpty())
                            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Nenhum edital encerrado encontrado.</div>
                        @else
                            <div class="space-y-3">
                                @foreach ($encerrados as $edital)
                                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-base font-bold text-slate-900">{{ $edital->titulo }}</h3>
                                                <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($edital->descricao ?: 'Sem descrição.', 220) }}</p>
                                                <p class="mt-1 text-xs font-semibold text-slate-700">Encerrado em {{ $edital->periodo_inscricao_fim->format('d/m/Y H:i') }}</p>
                                            </div>
                                            <span class="status-badge bg-slate-200 text-slate-700">Encerrado</span>
                                        </div>
                                        @if ($edital->hasArquivoEdital())
                                            <div class="mt-3">
                                                <a href="{{ route('public.editais.download', $edital) }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver edital</a>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                        </div>

                        <div x-show="editalTab === 'proximos'" x-transition>
                        @if ($proximos->isEmpty())
                            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Nenhum edital próximo encontrado.</div>
                        @else
                            <div class="space-y-3">
                                @foreach ($proximos as $edital)
                                    <article class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 shadow-sm">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-base font-bold text-slate-900">{{ $edital->titulo }}</h3>
                                                <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($edital->descricao ?: 'Sem descrição.', 220) }}</p>
                                                <p class="mt-1 text-xs font-semibold text-slate-700">Abre em {{ $edital->periodo_inscricao_inicio->format('d/m/Y H:i') }}</p>
                                            </div>
                                            <span class="status-badge bg-indigo-100 text-indigo-700">Próximo</span>
                                        </div>
                                        @if ($edital->hasArquivoEdital())
                                            <div class="mt-3">
                                                <a href="{{ route('public.editais.download', $edital) }}" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ver edital</a>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                        </div>
                    </div>
                </div>

                <div x-show="mainTab === 'verificar'" x-transition class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-base font-bold text-slate-900">Verificar inscrição</h2>
                        <p class="mt-1 text-xs text-slate-500">Informe protocolo, e-mail ou CPF para localizar sua inscrição.</p>
                        <form method="POST" action="{{ route('public.inscricoes.verificar') }}" class="mt-3 grid gap-3 md:grid-cols-[1fr_auto]">
                            @csrf
                            <input type="text" name="{{ $honeypotField }}" class="hidden" tabindex="-1" autocomplete="off">
                            <input type="text" name="busca" value="{{ old('busca', $consultaTermo) }}" placeholder="Protocolo, e-mail ou CPF" class="input-base" required>
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                Buscar
                            </button>
                        </form>
                        @error('busca')
                            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($consultaTermo !== '')
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <p class="mb-3 text-sm text-slate-600">Resultado da busca por: <strong>{{ $consultaTermo }}</strong></p>

                            @if (count($consultaResultados) === 0)
                                <p class="text-sm text-slate-600">Nenhuma inscrição encontrada para os dados informados.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach ($consultaResultados as $item)
                                        <article class="rounded-lg border border-slate-200 p-4 text-sm">
                                            <div class="grid gap-2 md:grid-cols-2">
                                                <p><strong>Protocolo:</strong> {{ $item['protocolo'] ?? '-' }}</p>
                                                <p><strong>Status:</strong> {{ $item['status'] ?? '-' }}</p>
                                                <p>
                                                    <strong>E-mail verificado:</strong>
                                                    @if (!empty($item['email_verificado']))
                                                        <span class="status-badge status-homologada">Sim</span>
                                                    @else
                                                        <span class="status-badge status-indeferida">Não verificado</span>
                                                    @endif
                                                </p>
                                                <p><strong>Nome:</strong> {{ $item['nome_completo'] ?? '-' }}</p>
                                                <p><strong>Edital:</strong> {{ $item['edital'] ?? '-' }}</p>
                                                <p><strong>E-mail:</strong> {{ $item['email'] ?? '-' }}</p>
                                                <p><strong>CPF:</strong> {{ $item['cpf'] ?? '-' }}</p>
                                                <p><strong>Enviado em:</strong> {{ $item['submitted_at'] ?? '-' }}</p>
                                                <p><strong>Decidido em:</strong> {{ $item['decided_at'] ?? '-' }}</p>
                                            </div>
                                            @if (empty($item['email_verificado']) && !empty($item['id']))
                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    <form method="POST" action="{{ route('public.inscricao.email.reenviar', $item['id']) }}">
                                                        @csrf
                                                        <input type="hidden" name="resend_key" value="{{ $item['resend_key'] ?? '' }}">
                                                        <button type="submit" class="btn-primary">Reenviar verificação de e-mail</button>
                                                    </form>
                                                    <p class="text-xs text-amber-700">Sem verificação, a candidatura pode ser indeferida automaticamente.</p>
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-amber-800">
                            <span class="inline-flex items-center rounded-full border border-amber-400 bg-amber-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide">
                                Atenção
                            </span>
                            <p class="mt-2 text-sm font-medium">Se houver erro de CPF ou e-mail, entre em contato com a secretaria com urgência.</p>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
        <script>
            function portalPublico(initialTab, initialConsultaResultados, initialConsultaTermo, initialStart, initialEnd) {
                return {
                    mainTab: initialTab || 'editais',
                    editalTab: 'abertos',
                    timer: null,
                    startDate: initialStart || '',
                    endDate: initialEnd || '',
                    consultaResultados: Array.isArray(initialConsultaResultados) ? initialConsultaResultados : [],
                    consultaTermo: initialConsultaTermo || '',
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
    </body>
</html>
