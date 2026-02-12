<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Inscricao - Fisioterapia (Ortopedia e Trauma)</h1>
        <p class="mt-2 text-sm text-gray-600">Preencha os dados e envie todos os PDFs obrigatorios.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('inscricao.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="nome_completo" value="Nome completo" />
            <x-text-input id="nome_completo" name="nome_completo" type="text" class="mt-1 block w-full" :value="old('nome_completo')" required />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
        </div>

        <div>
            <x-input-label for="cpf" value="CPF" />
            <x-text-input id="cpf" name="cpf" type="text" class="mt-1 block w-full" :value="old('cpf')" required />
        </div>

        <div>
            <x-input-label for="telefone" value="Telefone (opcional)" />
            <x-text-input id="telefone" name="telefone" type="text" class="mt-1 block w-full" :value="old('telefone')" />
        </div>

        @foreach ($tiposDocumentos as $tipo)
            <div>
                <x-input-label :for="'documentos_'.$tipo" :value="$tipo" />
                <input id="{{ 'documentos_'.$tipo }}" name="documentos[{{ $tipo }}]" type="file" accept="application/pdf" class="mt-1 block w-full rounded border border-gray-300 p-2 text-sm" required>
                <p class="mt-1 text-xs text-gray-500">Apenas PDF. Maximo: {{ (int) ($maxPdfKb / 1024) }} MB.</p>
            </div>
        @endforeach

        <div class="hidden" aria-hidden="true">
            <label for="{{ $honeypotField }}">Nao preencher</label>
            <input type="text" id="{{ $honeypotField }}" name="{{ $honeypotField }}" tabindex="-1" autocomplete="off">
        </div>

        <div>
            <x-primary-button>Enviar inscricao</x-primary-button>
        </div>
    </form>
</x-guest-layout>
