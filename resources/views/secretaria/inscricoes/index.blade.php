<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inscricoes - Secretaria</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="bg-white p-4 shadow sm:rounded-lg flex flex-col gap-3 md:flex-row md:items-end">
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 rounded-md border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        <option value="RECEBIDA" @selected($status === 'RECEBIDA')>RECEBIDA</option>
                        <option value="APROVADA" @selected($status === 'APROVADA')>APROVADA</option>
                        <option value="REJEITADA" @selected($status === 'REJEITADA')>REJEITADA</option>
                    </select>
                </div>
                <div class="flex-1">
                    <x-input-label for="q" value="Busca (nome/email)" />
                    <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="$search" />
                </div>
                <div>
                    <x-primary-button>Filtrar</x-primary-button>
                </div>
            </form>

            <div class="bg-white shadow sm:rounded-lg p-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-4">ID</th>
                            <th class="py-2 pr-4">Nome</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Enviado em</th>
                            <th class="py-2">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inscricoes as $inscricao)
                            <tr class="border-b">
                                <td class="py-2 pr-4">{{ $inscricao->id }}</td>
                                <td class="py-2 pr-4">{{ $inscricao->nome_completo }}</td>
                                <td class="py-2 pr-4">{{ $inscricao->email }}</td>
                                <td class="py-2 pr-4">{{ $inscricao->status }}</td>
                                <td class="py-2 pr-4">{{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td class="py-2">
                                    <a href="{{ route('secretaria.inscricoes.show', $inscricao) }}" class="text-indigo-600 hover:underline">Ver detalhe</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-3 text-gray-600" colspan="6">Nenhuma inscricao encontrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $inscricoes->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
