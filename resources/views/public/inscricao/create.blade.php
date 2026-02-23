<x-guest-layout>
    <div x-data="{ step: 1 }" class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-white/90 p-5 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Inscrição pública</h1>
            <p class="mt-1 text-sm text-slate-600"><strong>Edital:</strong> {{ $edital->titulo }}</p>

            @if ($edital->isAberto())
                <p class="mt-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Inscrições abertas até {{ $edital->periodo_inscricao_fim->format('d/m/Y H:i') }}</p>
            @else
                <p class="mt-2 inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Inscrições encerradas</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white/90 p-4 shadow-sm">
            <div class="grid gap-2 md:grid-cols-2">
                <button type="button" @click="step = 1" class="rounded-lg px-3 py-2 text-left text-sm font-semibold" :class="step === 1 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">1. Dados pessoais</button>
                <button type="button" @click="step = 2" class="rounded-lg px-3 py-2 text-left text-sm font-semibold" :class="step === 2 ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">2. Documentos PDF</button>
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
                    <x-text-input id="nome_completo" name="nome_completo" type="text" class="input-base" :value="old('nome_completo')" required />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="input-base" :value="old('email')" required />
                    </div>
                    <div>
                        <x-input-label for="cpf" value="CPF" />
                        <x-text-input id="cpf" name="cpf" type="text" class="input-base" :value="old('cpf')" required />
                    </div>
                </div>

                <div>
                    <x-input-label for="telefone" value="Telefone (opcional)" />
                    <x-text-input id="telefone" name="telefone" type="text" class="input-base" :value="old('telefone')" />
                </div>

                <div class="flex justify-end">
                    <button type="button" @click="step = 2" class="btn-primary">Continuar</button>
                </div>
            </section>

            <section x-show="step === 2" x-transition class="panel-card space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Checklist de documentos</h2>
                    <button type="button" @click="step = 1" class="btn-muted">Voltar</button>
                </div>

                <div class="space-y-3">
                    @foreach ($edital->documentosRequeridos as $doc)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-slate-800">{{ $doc->tipo }}</p>
                                <span class="status-badge {{ $doc->obrigatorio ? 'status-indeferida' : 'status-recebida' }}">
                                    {{ $doc->obrigatorio ? 'Obrigatório' : 'Opcional' }}
                                </span>
                            </div>

                            @if ($doc->descricao)
                                <p class="mt-1 text-xs text-slate-500">{{ $doc->descricao }}</p>
                            @endif

                            <input id="{{ 'documentos_'.$doc->tipo }}" name="documentos[{{ $doc->tipo }}]" type="file" accept="application/pdf" class="mt-2 block w-full rounded-lg border border-slate-300 p-2 text-sm" @if($doc->obrigatorio) required @endif>
                            <p class="mt-1 text-xs text-slate-500">Apenas PDF. Máximo: {{ (int) ($maxPdfKb / 1024) }} MB.</p>
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
