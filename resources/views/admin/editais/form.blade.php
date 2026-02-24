<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">{{ $edital->exists ? 'Editar edital' : 'Novo edital' }}</h2>
            <p class="text-sm text-slate-500">Configure período, publicação e documentos exigidos.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $publicadoInicial = (bool) old('publicado', $edital->exists ? $edital->publicado : false);
            $isEdit = $edital->exists;
            $hasArquivoAtual = (bool) $edital->arquivo_path;
        @endphp

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="panel-card space-y-6" x-data="editalDocsForm(@js($documentosInitial), {{ $publicadoInicial ? 'true' : 'false' }}, {{ $isEdit ? 'true' : 'false' }}, {{ $hasArquivoAtual ? 'true' : 'false' }})">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Status do Edital</p>
                        <p class="text-xs text-slate-500">Controle se o edital fica disponível para inscrição pública.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold" :class="publicado ? 'text-emerald-700' : 'text-slate-500'" x-text="publicado ? 'Publicado' : 'Rascunho'"></span>
                        <button type="button" @click="togglePublicado()" class="relative h-6 w-12 rounded-full transition" :class="publicado ? 'bg-emerald-500' : 'bg-slate-300'">
                            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white transition" :class="publicado ? 'left-6' : 'left-0.5'"></span>
                        </button>
                        <input type="hidden" name="publicado" :value="publicado ? 1 : 0">
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="titulo" value="Título" />
                    <x-text-input id="titulo" name="titulo" type="text" class="input-base" :value="old('titulo', $edital->titulo)" x-ref="titulo" required />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="descricao" value="Descrição" />
                    <textarea id="descricao" name="descricao" rows="3" class="input-base" x-ref="descricao">{{ old('descricao', $edital->descricao) }}</textarea>
                </div>

                <div>
                    <x-input-label for="periodo_inscricao_inicio" value="Início inscrição" />
                    <x-text-input id="periodo_inscricao_inicio" name="periodo_inscricao_inicio" type="datetime-local" class="input-base" :value="old('periodo_inscricao_inicio', optional($edital->periodo_inscricao_inicio)->format('Y-m-d\TH:i'))" x-ref="inicio" required />
                </div>

                <div>
                    <x-input-label for="periodo_inscricao_fim" value="Fim inscrição" />
                    <x-text-input id="periodo_inscricao_fim" name="periodo_inscricao_fim" type="datetime-local" class="input-base" :value="old('periodo_inscricao_fim', optional($edital->periodo_inscricao_fim)->format('Y-m-d\TH:i'))" x-ref="fim" required />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="arquivo_edital" value="Arquivo do edital (PDF)" />
                    <input
                        id="arquivo_edital"
                        name="arquivo_edital"
                        type="file"
                        accept="application/pdf"
                        x-ref="arquivoEdital"
                        @change="hasArquivoAtual = $event.target.files.length > 0 || {{ $hasArquivoAtual ? 'true' : 'false' }}"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700"
                    >
                    @if ($edital->arquivo_original_name)
                        <p class="mt-1 text-xs text-slate-500">
                            Arquivo atual: {{ $edital->arquivo_original_name }}
                            <a href="{{ route('public.editais.download', $edital) }}" class="font-semibold text-blue-700 hover:underline">Baixar</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Documentos exigidos</h3>
                    <p class="text-xs text-slate-500">A ordem de adição será a ordem exibida para o aluno.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <template x-for="(doc, index) in docs" :key="doc.key">
                        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-700" x-text="`Documento ${index + 1}`"></p>
                                <button type="button" @click="openRemoveModal(index)" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-red-50 text-lg font-semibold leading-none text-red-600 hover:bg-red-100" title="Remover documento">
                                    x
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome do documento</label>
                                    <input type="text" x-model="doc.tipo" :name="field(index, 'tipo')" class="input-base mt-1" maxlength="120" placeholder="Ex: Histórico escolar" required>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Formatos aceitos</label>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-slate-700">
                                        <label class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            <input type="checkbox" :name="fieldArray(index, 'formatos_aceitos')" value="pdf" x-model="doc.formatos_aceitos" class="rounded border-slate-300 text-blue-600">
                                            PDF
                                        </label>
                                        <label class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            <input type="checkbox" :name="fieldArray(index, 'formatos_aceitos')" value="docx" x-model="doc.formatos_aceitos" class="rounded border-slate-300 text-blue-600">
                                            DOCX
                                        </label>
                                        <label class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            <input type="checkbox" :name="fieldArray(index, 'formatos_aceitos')" value="jpg" x-model="doc.formatos_aceitos" class="rounded border-slate-300 text-blue-600">
                                            JPG
                                        </label>
                                        <label class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5">
                                            <input type="checkbox" :name="fieldArray(index, 'formatos_aceitos')" value="png" x-model="doc.formatos_aceitos" class="rounded border-slate-300 text-blue-600">
                                            PNG
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Descrição</label>
                                    <input type="text" x-model="doc.descricao" :name="field(index, 'descricao')" class="input-base mt-1" maxlength="255" placeholder="Detalhes para o candidato">
                                </div>

                                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Obrigatório</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold" :class="doc.obrigatorio ? 'text-emerald-700' : 'text-slate-500'" x-text="doc.obrigatorio ? 'Sim' : 'Não'"></span>
                                        <button type="button" @click="doc.obrigatorio = !doc.obrigatorio" class="relative h-6 w-11 rounded-full transition" :class="doc.obrigatorio ? 'bg-emerald-500' : 'bg-slate-300'">
                                            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white transition" :class="doc.obrigatorio ? 'left-5' : 'left-0.5'"></span>
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" :name="field(index, 'obrigatorio')" :value="doc.obrigatorio ? 1 : 0">
                            </div>
                        </article>
                    </template>

                    <button
                        type="button"
                        @click="addDocument()"
                        class="flex min-h-[340px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700"
                    >
                        <span class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full border border-slate-300 bg-white text-2xl font-bold">+</span>
                        <span class="text-sm font-semibold">Adicionar Documento</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-primary" x-text="primaryActionLabel()" @click="handlePrimaryAction()"></button>
                @if ($edital->exists)
                    <button type="button" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" @click="confirmDeleteOpen = true">Excluir edital</button>
                @endif
                <a href="{{ route('admin.editais.index') }}" class="btn-muted">Cancelar</a>
            </div>

            <div
                x-show="confirmRemoveIndex !== null"
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
                style="display: none;"
            >
                <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                    <h4 class="text-base font-semibold text-slate-900">Excluir documento</h4>
                    <p class="mt-2 text-sm text-slate-600">Deseja mesmo excluir o documento?</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" @click="confirmRemoveIndex = null" class="btn-muted">Cancelar</button>
                        <button type="button" @click="confirmRemove()" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">Excluir</button>
                    </div>
                </div>
            </div>

            <div
                x-show="publishBlockModalOpen"
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
                style="display: none;"
            >
                <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                    <h4 class="text-base font-semibold text-slate-900">Publicação bloqueada</h4>
                    <p class="mt-2 text-sm text-slate-600">Para publicar este edital, preencha os campos obrigatórios:</p>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                        <template x-for="item in publishMissingFields" :key="item">
                            <li x-text="item"></li>
                        </template>
                    </ul>
                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="publishBlockModalOpen = false" class="btn-primary">Entendi</button>
                    </div>
                </div>
            </div>

            @if ($edital->exists)
                <div
                    x-show="confirmDeleteOpen"
                    x-transition
                    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
                    style="display: none;"
                >
                    <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                        <h4 class="text-base font-semibold text-slate-900">Excluir edital</h4>
                        <p class="mt-2 text-sm text-slate-600">Deseja mesmo excluir este edital?</p>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" @click="confirmDeleteOpen = false" class="btn-muted">Cancelar</button>
                            <button type="button" @click="submitDelete()" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">Excluir</button>
                        </div>
                    </div>
                </div>
            @endif
        </form>

        @if ($edital->exists)
            <form id="delete-edital-form" method="POST" action="{{ route('admin.editais.destroy', $edital) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>

    <script>
        function editalDocsForm(initialDocs, publicadoInicial, isEdit, hasArquivoPersistido) {
            const normalized = (Array.isArray(initialDocs) ? initialDocs : []).map((doc, idx) => ({
                key: `doc-${Date.now()}-${idx}`,
                tipo: doc?.tipo ?? '',
                formatos_aceitos: Array.isArray(doc?.formatos_aceitos) && doc.formatos_aceitos.length > 0
                    ? doc.formatos_aceitos
                    : ['pdf'],
                descricao: doc?.descricao ?? '',
                obrigatorio: !['0', 0, false, 'false', null].includes(doc?.obrigatorio ?? true),
            }));

            return {
                docs: normalized,
                publicado: !!publicadoInicial,
                isEdit: !!isEdit,
                hasArquivoAtual: !!hasArquivoPersistido,
                confirmRemoveIndex: null,
                confirmDeleteOpen: false,
                publishBlockModalOpen: false,
                publishMissingFields: [],
                field(index, name) {
                    return `documentos_requeridos[${index}][${name}]`;
                },
                fieldArray(index, name) {
                    return `documentos_requeridos[${index}][${name}][]`;
                },
                addDocument() {
                    this.docs.push({
                        key: `doc-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        tipo: '',
                        formatos_aceitos: ['pdf'],
                        descricao: '',
                        obrigatorio: true,
                    });
                },
                openRemoveModal(index) {
                    this.confirmRemoveIndex = index;
                },
                confirmRemove() {
                    if (this.confirmRemoveIndex !== null) {
                        this.docs.splice(this.confirmRemoveIndex, 1);
                    }
                    this.confirmRemoveIndex = null;
                },
                requiredForPublishMissing() {
                    const missing = [];
                    const titulo = this.$refs.titulo?.value?.trim() ?? '';
                    const descricao = this.$refs.descricao?.value?.trim() ?? '';
                    const inicio = this.$refs.inicio?.value?.trim() ?? '';
                    const fim = this.$refs.fim?.value?.trim() ?? '';

                    if (!titulo) missing.push('Título');
                    if (!descricao) missing.push('Descrição');
                    if (!inicio) missing.push('Início da inscrição');
                    if (!fim) missing.push('Fim da inscrição');
                    if (!this.hasArquivoAtual) missing.push('Arquivo PDF do edital');

                    return missing;
                },
                togglePublicado() {
                    if (this.publicado) {
                        this.publicado = false;
                        return;
                    }

                    const missing = this.requiredForPublishMissing();
                    if (missing.length > 0) {
                        this.publishMissingFields = missing;
                        this.publishBlockModalOpen = true;
                        return;
                    }

                    this.publicado = true;
                },
                primaryActionLabel() {
                    if (!this.publicado) {
                        return 'Publicar Edital';
                    }

                    return this.isEdit ? 'Salvar Edições' : 'Publicar Edital';
                },
                handlePrimaryAction() {
                    if (!this.publicado) {
                        const missing = this.requiredForPublishMissing();
                        if (missing.length > 0) {
                            this.publishMissingFields = missing;
                            this.publishBlockModalOpen = true;
                            return;
                        }

                        this.publicado = true;
                    }

                    this.$nextTick(() => {
                        this.$root.submit();
                    });
                },
                submitDelete() {
                    const deleteForm = document.getElementById('delete-edital-form');
                    if (deleteForm) {
                        deleteForm.submit();
                    }
                },
            }
        }
    </script>
</x-app-layout>
