<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inscricao #{{ $inscricao->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-2 text-sm">
                <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
                <p><strong>Email:</strong> {{ $inscricao->email }}</p>
                <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
                <p><strong>Telefone:</strong> {{ $inscricao->telefone ?: '-' }}</p>
                <p><strong>Status:</strong> {{ $inscricao->status }}</p>
                <p><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
                <p><strong>Enviado em:</strong> {{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</p>
                <p><strong>Decidido em:</strong> {{ optional($inscricao->decided_at)->format('d/m/Y H:i') ?: '-' }}</p>
                <p><strong>Decidido por:</strong> {{ optional($inscricao->decidedByUser)->name ?: '-' }}</p>
                <p><strong>Motivo rejeicao:</strong> {{ $inscricao->rejection_reason ?: '-' }}</p>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Documentos</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($inscricao->documentos as $doc)
                        <li class="flex items-center justify-between border rounded p-2">
                            <span>{{ $doc->tipo }} ({{ $doc->original_name }})</span>
                            <a href="{{ route('secretaria.inscricoes.documentos.download', [$inscricao, $doc]) }}" class="text-indigo-600 hover:underline">Download</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($inscricao->status === 'RECEBIDA')
                <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                    <div>
                        <form method="POST" action="{{ route('secretaria.inscricoes.aprovar', $inscricao) }}">
                            @csrf
                            <x-primary-button>Aprovar e provisionar usuario</x-primary-button>
                        </form>
                    </div>

                    <div>
                        <form method="POST" action="{{ route('secretaria.inscricoes.rejeitar', $inscricao) }}" class="space-y-2">
                            @csrf
                            <x-input-label for="rejection_reason" value="Motivo da rejeicao" />
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" class="w-full rounded-md border-gray-300 shadow-sm" required>{{ old('rejection_reason') }}</textarea>
                            <x-danger-button>Rejeitar inscricao</x-danger-button>
                        </form>
                    </div>
                </div>
            @endif

            <a href="{{ route('secretaria.inscricoes.index') }}" class="text-sm text-indigo-600 hover:underline">Voltar para listagem</a>
        </div>
    </div>
</x-app-layout>
