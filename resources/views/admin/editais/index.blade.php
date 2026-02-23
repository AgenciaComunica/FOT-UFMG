<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Gestão de Editais</h2>
            <p class="text-sm text-slate-500">Configure períodos de inscrição e documentos obrigatórios.</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->has('edital'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('edital') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.editais.create') }}" class="btn-primary">Novo edital</a>
        </div>

        <div class="table-wrap">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Período</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($editais as $edital)
                        @php
                            $statusClass = match($edital->status) {
                                'ABERTO' => 'status-homologada',
                                'ENCERRADO' => 'status-indeferida',
                                default => 'status-recebida',
                            };
                        @endphp
                        <tr>
                            <td>{{ $edital->id }}</td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $edital->titulo }}</p>
                                <p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($edital->descricao, 70) }}</p>
                            </td>
                            <td>{{ $edital->periodo_inscricao_inicio->format('d/m/Y H:i') }} - {{ $edital->periodo_inscricao_fim->format('d/m/Y H:i') }}</td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $edital->status }}</span></td>
                            <td>
                                <div class="flex flex-wrap items-center gap-3">
                                    <a href="{{ route('admin.editais.edit', $edital) }}" class="text-blue-600 hover:underline">Editar</a>
                                    <a href="{{ route('admin.editais.inscricoes.index', $edital) }}" class="text-blue-600 hover:underline">Inscrições</a>
                                    <form method="POST" action="{{ route('admin.editais.destroy', $edital) }}" onsubmit="return confirm('Excluir edital?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-slate-500">Nenhum edital cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $editais->links() }}</div>
    </div>
</x-app-layout>
