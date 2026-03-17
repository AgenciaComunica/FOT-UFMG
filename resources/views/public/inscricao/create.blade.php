<x-guest-layout>
    <div
        x-data="{
            step: 1,
            nome: @js(old('nome_completo', '')),
            email: @js(old('email', '')),
            cpf: @js(old('cpf', '')),
            canGoDocs() {
                return this.nome.trim() !== '' && this.email.trim() !== '' && this.cpf.trim() !== '';
            }
        }"
        class="space-y-5"
    >
        <div class="rounded-xl border border-slate-200 bg-white/90 p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Inscrição pública</h1>
                    <p class="mt-1 text-sm text-slate-600"><strong>Edital:</strong> {{ $edital->titulo }}</p>
                    @if ($edital->isAberto())
                        <p class="mt-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Inscrições abertas até {{ $edital->periodo_inscricao_fim->format('d/m/Y H:i') }}</p>
                    @else
                        <p class="mt-2 inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Inscrições encerradas</p>
                    @endif
                </div>
                <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" class="w-[280px] h-auto md:w-[340px]">
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white/90 p-4 shadow-sm">
            <div class="grid gap-2 md:grid-cols-2">
                <button type="button" @click="step = 1" class="rounded-lg px-3 py-2 text-left text-sm font-semibold" :class="step === 1 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">1. Dados pessoais</button>
                <button
                    type="button"
                    @click="if (canGoDocs()) step = 2"
                    :disabled="!canGoDocs()"
                    class="rounded-lg px-3 py-2 text-left text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                    :class="step === 2 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'"
                >
                    2. Documentos
                </button>
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

        <form method="POST" action="{{ route('public.inscricao.store', $edital) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <section x-show="step === 1" x-transition class="panel-card space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Dados do candidato</h2>

                <div>
                    <x-input-label for="nome_completo" value="Nome completo" />
                    <x-text-input id="nome_completo" name="nome_completo" type="text" class="input-base" :value="old('nome_completo')" x-model="nome" required />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="cpf" value="CPF" />
                        <x-text-input id="cpf" name="cpf" type="text" class="input-base" :value="old('cpf')" x-model="cpf" required />
                    </div>
                    <div>
                        <x-input-label for="telefone" value="Telefone (opcional)" />
                        <x-text-input id="telefone" name="telefone" type="text" class="input-base" :value="old('telefone')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="input-base" :value="old('email')" x-model="email" required />
                </div>

                <div class="flex justify-end">
                    <button type="button" @click="step = 2" :disabled="!canGoDocs()" class="btn-primary disabled:cursor-not-allowed disabled:opacity-60">Continuar</button>
                </div>
            </section>

            <section x-show="step === 2" x-transition class="panel-card space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Checklist de documentos</h2>
                    <button type="button" @click="step = 1" class="btn-muted">Voltar</button>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($edital->documentosRequeridos as $doc)
                        @php
                            $defaultBadgeClass = $doc->obrigatorio ? 'status-indeferida' : 'status-recebida';
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
                        <div class="rounded-lg border border-slate-200 p-3" x-data="{ fileName: 'Nenhum arquivo selecionado', hasFile: false }">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-slate-800">{{ $doc->tipo }}</p>
                                <span
                                    class="status-badge"
                                    :class="hasFile ? 'status-homologada' : '{{ $defaultBadgeClass }}'"
                                >
                                    {{ $doc->obrigatorio ? 'Obrigatório' : 'Opcional' }}
                                </span>
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
                                    title="Adicionar arquivo"
                                >
                                    <svg x-show="!hasFile" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M4 2.5A1.5 1.5 0 0 0 2.5 4v12A1.5 1.5 0 0 0 4 17.5h12a1.5 1.5 0 0 0 1.5-1.5V7.6a1.5 1.5 0 0 0-.44-1.06l-3.6-3.6A1.5 1.5 0 0 0 12.4 2.5H4z" fill="none" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M12.5 2.8V6a1 1 0 0 0 1 1h3.2" fill="none" stroke="currentColor" stroke-width="1.4"/>
                                        <path d="M10 10.2v3.6M8.2 12h3.6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                    </svg>
                                    <svg x-show="hasFile" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 011.414-1.414l2.543 2.543 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </label>
                            </div>

                            <input
                                id="{{ 'documentos_'.$doc->id }}"
                                name="documentos[{{ $doc->id }}]"
                                type="file"
                                accept="{{ $accept }}"
                                class="sr-only"
                                @change="hasFile = $event.target.files.length > 0; fileName = hasFile ? $event.target.files[0].name : 'Nenhum arquivo selecionado'"
                                @if($doc->obrigatorio) required @endif
                            >
                            <p class="mt-1 text-xs text-slate-500">Formatos: {{ strtoupper(implode(', ', $doc->formatos_aceitos)) }}. Máximo: {{ (int) ($maxPdfKb / 1024) }} MB.</p>
                        </div>
                    @endforeach
                </div>

                <div class="hidden" aria-hidden="true">
                    <label for="{{ $honeypotField }}">Não preencher</label>
                    <input type="text" id="{{ $honeypotField }}" name="{{ $honeypotField }}" tabindex="-1" autocomplete="off">
                </div>

                <div class="flex justify-end">
                    <x-primary-button :disabled="!$edital->isAberto()">Enviar inscrição</x-primary-button>
                </div>
            </section>
        </form>
    </div>
</x-guest-layout>
