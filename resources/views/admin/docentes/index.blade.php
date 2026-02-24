<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Docentes</h2>
            <p class="text-sm text-slate-500">Gestão de docentes para o futuro painel de classificação.</p>
        </div>
    </x-slot>

    <div
        class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8"
        x-data="{
            timer: null,
            openImportModal: {{ $errors->has('arquivo') ? 'true' : 'false' }},
            openImportResultModal: {{ session()->has('import_result') ? 'true' : 'false' }}
        }"
    >
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <form method="GET" class="panel-card grid gap-3 md:grid-cols-[1fr_auto] md:items-end" x-ref="filterForm">
            <div>
                <x-input-label for="q" value="Pesquisar docente" />
                <x-text-input
                    id="q"
                    name="q"
                    type="text"
                    class="input-base"
                    :value="$q"
                    placeholder="Nome, e-mail ou telefone"
                    @input="clearTimeout(timer); timer = setTimeout(() => $refs.filterForm.submit(), 350)"
                />
            </div>
            <div class="flex gap-2">
                @if ($q !== '')
                    <a href="{{ route('admin.docentes.index') }}" class="btn-muted">Limpar</a>
                @endif
                <button type="button" class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" @click="openImportModal = true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6 15a3 3 0 010-6h.26A5 5 0 1115 10h.5a2.5 2.5 0 010 5H6z" />
                        <path d="M10 8a1 1 0 011 1v3h1.586L10 14.586 7.414 12H9V9a1 1 0 011-1z" />
                    </svg>
                    Importar Docentes
                </button>
                <a href="{{ route('admin.docentes.create') }}" class="btn-success">+ Novo Docente</a>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($docentes as $docente)
                        <tr>
                            <td class="font-semibold text-slate-700">{{ $docente->name }}</td>
                            <td>{{ $docente->email }}</td>
                            <td>{{ $docente->telefone ?: '-' }}</td>
                            <td class="align-middle">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.docentes.status', $docente) }}" class="m-0">
                                        @csrf
                                        <input type="hidden" name="ativo" value="0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-slate-600">{{ $docente->ativo ? 'Ativo' : 'Inativo' }}</span>
                                            <label class="relative inline-flex h-6 w-11 cursor-pointer items-center">
                                                <input
                                                    type="checkbox"
                                                    name="ativo"
                                                    value="1"
                                                    class="peer sr-only"
                                                    @checked($docente->ativo)
                                                    onchange="this.form.submit()"
                                                >
                                                <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span>
                                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                            </label>
                                        </div>
                                    </form>
                                    <a href="{{ route('admin.docentes.edit', $docente) }}" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 010 2.828l-9.9 9.9a1 1 0 01-.46.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.46l9.9-9.9a2 2 0 012.828 0z" />
                                        </svg>
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('admin.docentes.destroy', $docente) }}" class="m-0" onsubmit="return confirm('Deseja excluir este docente?');">
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
                            <td colspan="4" class="text-slate-500">Nenhum docente encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $docentes->links() }}</div>

        <div
            x-show="openImportModal"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            style="display: none;"
        >
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Importar docentes</h3>
                        <p class="mt-1 text-sm text-slate-600">Envie um arquivo CSV ou XLSX com colunas de nome e email.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="openImportModal = false">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.docentes.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="arquivo" value="Arquivo (CSV ou XLSX)" />
                        <input id="arquivo" name="arquivo" type="file" accept=".csv,.txt,.xlsx" class="input-base mt-1">
                        <x-input-error :messages="$errors->get('arquivo')" class="mt-2" />
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        Formato esperado:
                        <br>Colunas com cabeçalho `nome` e `email` (telefone opcional), ou nome/email nas duas primeiras colunas.
                        <br>
                        <a href="{{ route('admin.docentes.template') }}" class="mt-1 inline-flex font-semibold text-blue-600 hover:underline">Baixar modelo Excel (CSV)</a>
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
                        {{ $importResult['importados'] ?? 0 }} docentes cadastrados,
                        {{ $importResult['falhas_total'] ?? 0 }} docentes NÃO foram cadastrados, ver abaixo.
                    </p>

                    @if (!empty($importResult['falhas']))
                        <div class="mt-3 max-h-72 overflow-auto rounded-md border border-red-200 bg-red-50 p-3">
                            <ul class="space-y-1 text-sm text-red-700">
                                @foreach ($importResult['falhas'] as $falha)
                                    <li>Docente da linha {{ $falha['linha'] ?? '?' }} faltou/erro: {{ $falha['motivo'] ?? 'dado inválido' }}</li>
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
    </div>
</x-app-layout>
