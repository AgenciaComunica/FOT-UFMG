<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Inscrição {{ $inscricao->protocolo }}</h2>
            <p class="text-sm text-slate-500">{{ $inscricao->edital?->titulo }}</p>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8"
         x-data="{ tab: 'dados', modalHomologar: false, modalIndeferir: false }">

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @if (session('senha_temporaria'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                Senha temporária gerada (exibida uma vez): <strong>{{ session('senha_temporaria') }}</strong>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel-card">
            <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                <button type="button" @click="tab = 'dados'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'dados' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Dados</button>
                <button type="button" @click="tab = 'documentos'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'documentos' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Documentos</button>
            </div>

            <div x-show="tab === 'dados'" x-transition class="space-y-2 text-sm text-slate-700">
                @php
                    $statusClass = match($inscricao->status) {
                        'HOMOLOGADA' => 'status-homologada',
                        'INDEFERIDA' => 'status-indeferida',
                        default => 'status-recebida',
                    };
                @endphp
                <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
                <p><strong>Email:</strong> {{ $inscricao->email }}</p>
                <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
                <p><strong>Telefone:</strong> {{ $inscricao->telefone ?: '-' }}</p>
                <p><strong>Status:</strong> <span class="status-badge {{ $statusClass }}">{{ $inscricao->status }}</span></p>
                <p><strong>Enviado em:</strong> {{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</p>
                <p><strong>Decidido em:</strong> {{ optional($inscricao->decided_at)->format('d/m/Y H:i') ?: '-' }}</p>
                <p><strong>Decidido por:</strong> {{ optional($inscricao->decidedByUser)->name ?: '-' }}</p>
                <p><strong>Motivo indeferimento:</strong> {{ $inscricao->indeferimento_motivo ?: '-' }}</p>
            </div>

            <div x-show="tab === 'documentos'" x-transition class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Checklist obrigatórios</h3>
                    <div class="mt-2 grid gap-2 md:grid-cols-2">
                        @foreach ($inscricao->edital->documentosRequeridos->where('obrigatorio', true) as $req)
                            @php
                                $ok = $inscricao->documentos->contains(fn($doc) => $doc->tipo === $req->tipo);
                            @endphp
                            <div class="rounded-lg border p-2 text-sm {{ $ok ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-300 bg-red-50 text-red-700' }}">
                                {{ $req->tipo }}: {{ $ok ? 'OK' : 'FALTANDO' }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Arquivos enviados</h3>
                    <ul class="mt-2 space-y-2">
                        @foreach ($inscricao->documentos as $doc)
                            <li class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm">
                                <span>{{ $doc->tipo }} ({{ $doc->original_name }})</span>
                                <a href="{{ route('admin.inscricoes.documentos.download', [$inscricao, $doc]) }}" class="text-blue-600 hover:underline">Download</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        @if ($inscricao->status === 'RECEBIDA')
            <div class="panel-card">
                <div class="flex flex-wrap gap-3">
                    <button type="button" class="btn-success" :disabled="{{ $podeHomologar ? 'false' : 'true' }}" @click="modalHomologar = true">Homologar</button>
                    <button type="button" class="btn-danger" @click="modalIndeferir = true">Indeferir</button>
                </div>
                @if (! $podeHomologar)
                    <p class="mt-2 text-xs text-red-600">Só é possível homologar quando todos os documentos obrigatórios estiverem presentes.</p>
                @endif
            </div>

            <div x-show="modalHomologar" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @click.self="modalHomologar = false">
                <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-900">Confirmar homologação</h3>
                    <p class="mt-2 text-sm text-slate-600">Esta ação cria/libera o usuário aluno e marca a inscrição como homologada.</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="btn-muted" @click="modalHomologar = false">Cancelar</button>
                        <form method="POST" action="{{ route('admin.inscricoes.homologar', $inscricao) }}">
                            @csrf
                            <button type="submit" class="btn-success">Confirmar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div x-show="modalIndeferir" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @click.self="modalIndeferir = false">
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-900">Indeferir inscrição</h3>
                    <form method="POST" action="{{ route('admin.inscricoes.indeferir', $inscricao) }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="indeferimento_motivo" value="Motivo (obrigatório)" />
                            <textarea id="indeferimento_motivo" name="indeferimento_motivo" rows="4" class="input-base" required>{{ old('indeferimento_motivo') }}</textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-muted" @click="modalIndeferir = false">Cancelar</button>
                            <button type="submit" class="btn-danger">Confirmar indeferimento</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <a href="{{ route('admin.editais.inscricoes.index', $inscricao->edital) }}" class="text-sm font-semibold text-blue-600 hover:underline">Voltar para listagem</a>
    </div>
</x-app-layout>
