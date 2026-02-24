<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $isEdit ? 'Editar docente' : 'Novo docente' }}</h2>
                <p class="text-sm text-slate-500">Cadastro de acesso para docentes avaliadores.</p>
            </div>
            <a href="{{ route('admin.docentes.index') }}" class="btn-muted">Voltar para Docentes</a>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ $formAction }}" class="panel-card space-y-5">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-4" x-data="{ ativo: {{ old('ativo', $docente->exists ? (int) $docente->ativo : 1) ? 'true' : 'false' }} }">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Status do docente</p>
                            <p class="text-xs text-slate-500">Controle se o docente pode acessar o sistema.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-500" x-text="ativo ? 'Ativo' : 'Inativo'"></span>
                            <input type="hidden" name="ativo" value="0">
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    name="ativo"
                                    value="1"
                                    class="peer sr-only"
                                    x-model="ativo"
                                    @checked(old('ativo', $docente->exists ? (int) $docente->ativo : 1))
                                >
                                <div class="h-6 w-11 rounded-full transition" :class="ativo ? 'bg-emerald-500' : 'bg-slate-300'"></div>
                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition" :class="ativo ? 'translate-x-5' : ''"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="name" value="Nome completo" />
                    <x-text-input id="name" name="name" type="text" class="input-base" :value="old('name', $docente->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="E-mail" />
                    <x-text-input id="email" name="email" type="email" class="input-base" :value="old('email', $docente->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="telefone" value="Telefone (opcional)" />
                    <x-text-input id="telefone" name="telefone" type="text" class="input-base" :value="old('telefone', $docente->telefone)" placeholder="(31) 99999-9999" />
                    <x-input-error :messages="$errors->get('telefone')" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('admin.docentes.index') }}" class="btn-muted">Cancelar</a>
                <x-primary-button>{{ $isEdit ? 'Salvar alterações' : 'Cadastrar docente' }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
