<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Painel do Aluno</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @if ($inscricao)
                    <p class="text-sm"><strong>Status da inscricao:</strong> {{ $inscricao->status }}</p>
                    <p class="text-sm"><strong>Protocolo:</strong> {{ $inscricao->protocolo }}</p>
                    <p class="text-sm"><strong>Email:</strong> {{ $inscricao->email }}</p>
                @else
                    <p class="text-sm text-gray-600">Nenhuma inscricao vinculada a este usuario.</p>
                @endif
            </div>

            @if ($inscricao)
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Documentos enviados</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach ($inscricao->documentos as $doc)
                            <li class="flex items-center justify-between border rounded p-2">
                                <span>{{ $doc->tipo }} ({{ $doc->original_name }})</span>
                                <a href="{{ route('aluno.documentos.download', $doc) }}" class="text-indigo-600 hover:underline">Download</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
