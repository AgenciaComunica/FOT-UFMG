<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">{{ $edital->exists ? 'Editar edital' : 'Novo edital' }}</h2>
            <p class="text-sm text-slate-500">Defina período e documentos exigidos para a inscrição.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-4xl px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" class="panel-card space-y-5">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div>
                <x-input-label for="titulo" value="Título" />
                <x-text-input id="titulo" name="titulo" type="text" class="input-base" :value="old('titulo', $edital->titulo)" required />
            </div>

            <div>
                <x-input-label for="descricao" value="Descrição" />
                <textarea id="descricao" name="descricao" rows="3" class="input-base">{{ old('descricao', $edital->descricao) }}</textarea>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="periodo_inscricao_inicio" value="Início inscrição" />
                    <x-text-input id="periodo_inscricao_inicio" name="periodo_inscricao_inicio" type="datetime-local" class="input-base" :value="old('periodo_inscricao_inicio', optional($edital->periodo_inscricao_inicio)->format('Y-m-d\TH:i'))" required />
                </div>
                <div>
                    <x-input-label for="periodo_inscricao_fim" value="Fim inscrição" />
                    <x-text-input id="periodo_inscricao_fim" name="periodo_inscricao_fim" type="datetime-local" class="input-base" :value="old('periodo_inscricao_fim', optional($edital->periodo_inscricao_fim)->format('Y-m-d\TH:i'))" required />
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-base font-semibold text-slate-900">Documentos do edital</h3>

                @foreach ($tiposDocumentos as $index => $tipo)
                    @php
                        $selected = old("documentos_requeridos.$index", $selectedDocumentos[$tipo] ?? []);
                    @endphp
                    <div class="rounded-lg border border-slate-200 p-4">
                        <input type="hidden" name="documentos_requeridos[{{ $index }}][tipo]" value="{{ $tipo }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-800">{{ $tipo }}</p>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="documentos_requeridos[{{ $index }}][obrigatorio]" value="1" @checked((bool) ($selected['obrigatorio'] ?? false))>
                                Obrigatório
                            </label>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <x-input-label :for="'descricao_'.$index" value="Descrição" />
                                <x-text-input :id="'descricao_'.$index" :name="'documentos_requeridos['.$index.'][descricao]'" type="text" class="input-base" :value="$selected['descricao'] ?? ''" />
                            </div>
                            <div>
                                <x-input-label :for="'ordem_'.$index" value="Ordem" />
                                <x-text-input :id="'ordem_'.$index" :name="'documentos_requeridos['.$index.'][ordem]'" type="number" min="1" class="input-base" :value="$selected['ordem'] ?? ($index + 1)" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3">
                <x-primary-button>Salvar edital</x-primary-button>
                <a href="{{ route('admin.editais.index') }}" class="btn-muted">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
