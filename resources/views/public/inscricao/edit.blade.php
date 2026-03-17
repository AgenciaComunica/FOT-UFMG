<x-guest-layout>
    <div class="space-y-5" x-data="{ confirmModal: false, motivoEdicao: @js(old('motivo_edicao', '')) }">
        <div class="rounded-xl border border-slate-200 bg-white/90 p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Editar inscrição</h1>
                    <p class="mt-1 text-sm text-slate-600"><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
                    <p class="mt-1 text-sm text-slate-600"><strong>Edital:</strong> {{ $edital->titulo }}</p>
                    <p class="mt-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Link válido até {{ optional($inscricao->edit_link_expires_at)->format('d/m/Y H:i') }}</p>
                </div>
                <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" class="w-[280px] h-auto md:w-[340px]">
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="inscricao-edit-form" method="POST" action="{{ route('public.inscricoes.editar.update', ['inscricao' => $inscricao->id, 'token' => $editToken]) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="motivo_edicao" x-model="motivoEdicao">

            <section class="panel-card space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Dados do candidato</h2>

                <div>
                    <x-input-label for="nome_completo" value="Nome completo" />
                    <x-text-input id="nome_completo" name="nome_completo" type="text" class="input-base" :value="old('nome_completo', $inscricao->nome_completo)" required />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="cpf" value="CPF" />
                        <x-text-input id="cpf" name="cpf" type="text" class="input-base" :value="old('cpf', $inscricao->cpf)" required />
                    </div>
                    <div>
                        <x-input-label for="telefone" value="Telefone (opcional)" />
                        <x-text-input id="telefone" name="telefone" type="text" class="input-base" :value="old('telefone', $inscricao->telefone)" />
                    </div>
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="input-base" :value="old('email', $inscricao->email)" required />
                </div>
            </section>

            <section class="panel-card space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Documentos</h2>
                <p class="text-xs text-slate-500">Você pode substituir arquivos enviando novos documentos nos campos abaixo.</p>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($edital->documentosRequeridos as $doc)
                        @php
                            $existente = $inscricao->documentos->firstWhere('tipo', $doc->tipo);
                            $acceptMap = [
                                'pdf' => '.pdf,application/pdf',
                                'docx' => '.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'jpg' => '.jpg,.jpeg,image/jpeg',
                                'png' => '.png,image/png',
                            ];
                            $accept = collect($doc->formatos_aceitos)
                                ->map(fn ($ext) => $acceptMap[$ext] ?? null)
                                ->filter()
                                ->implode(',');
                        @endphp
                        <div class="rounded-lg border border-slate-200 p-3" x-data="{ fileName: '{{ $existente?->original_name ?: 'Nenhum arquivo selecionado' }}', hasFile: false }">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-slate-800">{{ $doc->tipo }}</p>
                                @if ($doc->obrigatorio)
                                    <span class="status-badge status-indeferida">Obrigatório</span>
                                @else
                                    <span class="status-badge status-recebida">Opcional</span>
                                @endif
                            </div>
                            @if ($doc->descricao)
                                <p class="mt-1 text-xs text-slate-500">{{ $doc->descricao }}</p>
                            @endif

                            <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-slate-300 bg-white/80 p-0">
                                <p class="truncate px-3 text-xs text-slate-600" x-text="fileName"></p>
                                <label
                                    for="{{ 'documentos_'.$doc->id }}"
                                    class="inline-flex cursor-pointer items-center rounded-r-lg border-l px-4 py-2.5 text-xs font-semibold text-white transition"
                                    :class="hasFile ? 'border-emerald-700 bg-emerald-600 hover:bg-emerald-700' : 'border-blue-700 bg-blue-600 hover:bg-blue-700'"
                                    title="Substituir arquivo"
                                >
                                    Substituir
                                </label>
                            </div>

                            <input
                                id="{{ 'documentos_'.$doc->id }}"
                                name="documentos[{{ $doc->id }}]"
                                type="file"
                                accept="{{ $accept }}"
                                class="sr-only"
                                @change="hasFile = $event.target.files.length > 0; fileName = hasFile ? $event.target.files[0].name : fileName"
                            >
                            <p class="mt-1 text-xs text-slate-500">Formatos: {{ strtoupper(implode(', ', $doc->formatos_aceitos)) }}. Máximo: {{ (int) ($maxPdfKb / 1024) }} MB.</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end">
                <button type="button" class="btn-primary" @click="confirmModal = true">Salvar alterações</button>
            </div>
        </form>

        <div x-show="confirmModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="confirmModal = false">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Confirmar edição da inscrição</h3>
                <p class="mt-1 text-sm text-slate-600">Informe o motivo da edição. Esse histórico ficará disponível para a secretaria.</p>
                <div class="mt-3">
                    <x-input-label for="motivo_edicao_modal" value="Motivo da edição" />
                    <textarea
                        id="motivo_edicao_modal"
                        class="input-base mt-1"
                        rows="4"
                        maxlength="1000"
                        x-model="motivoEdicao"
                        placeholder="Ex.: correção de e-mail, atualização de documento, ajuste de CPF"
                        required
                    ></textarea>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="btn-muted" @click="confirmModal = false">Cancelar</button>
                    <button
                        type="submit"
                        form="inscricao-edit-form"
                        class="btn-primary"
                        :disabled="!motivoEdicao || motivoEdicao.trim().length < 5"
                    >
                        Confirmar e salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
