<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $isEdit ? 'Editar lead' : 'Novo lead' }}</h2>
                <p class="text-sm text-slate-500">Cadastro para comunicação sobre editais do curso.</p>
            </div>
            <a href="{{ route('admin.leads.index') }}" class="btn-muted">Voltar para Leads</a>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" class="panel-card space-y-4">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="nome" value="Nome" />
                    <x-text-input id="nome" name="nome" type="text" class="input-base mt-1" :value="old('nome', $lead->nome)" required />
                </div>
                <div>
                    <x-input-label for="email" value="E-mail" />
                    <x-text-input id="email" name="email" type="email" class="input-base mt-1" :value="old('email', $lead->email)" required />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('admin.leads.index') }}" class="btn-muted">Cancelar</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Salvar alterações' : 'Cadastrar lead' }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
