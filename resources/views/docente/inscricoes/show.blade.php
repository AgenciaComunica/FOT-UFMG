<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Avaliar Inscrição {{ $inscricao->protocolo }}</h2>
                <p class="text-sm text-slate-500">{{ $inscricao->edital?->titulo }}</p>
            </div>
            <a href="{{ route('docente.inscricoes.index') }}" class="btn-muted">Voltar para Inscrições</a>
        </div>
    </x-slot>

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="{ tab: 'dados' }">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
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

        <section class="panel-card">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Sua avaliação</h3>
                    <p class="text-xs text-slate-500">Preencha nota e comentário para esta candidatura.</p>
                </div>
                <span class="status-badge {{ $statusAvaliacao === 'AVALIADO' ? 'status-homologada' : 'bg-blue-100 text-blue-700' }}">
                    {{ $statusAvaliacao === 'AVALIADO' ? 'Avaliado' : 'Avaliação Pendente' }}
                </span>
            </div>

            <form method="POST" action="{{ route('docente.inscricoes.salvar', $inscricao) }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="nota" value="Nota (0 a 10)" />
                        <x-text-input id="nota" name="nota" type="number" min="0" max="10" step="0.01" class="input-base" :value="old('nota', $avaliacao?->nota)" required />
                    </div>
                    <div>
                        <x-input-label for="avaliacao_subjetiva" value="Avaliação Subjetiva" />
                        <select id="avaliacao_subjetiva" name="avaliacao_subjetiva" class="input-base">
                            <option value="HOMOLOGAR" @selected(old('avaliacao_subjetiva', $avaliacao?->avaliacao_subjetiva ?? 'ABSTER') === 'HOMOLOGAR')>✓ Homologar</option>
                            <option value="INDEFERIR" @selected(old('avaliacao_subjetiva', $avaliacao?->avaliacao_subjetiva ?? 'ABSTER') === 'INDEFERIR')>✕ Indeferir</option>
                            <option value="ABSTER" @selected(old('avaliacao_subjetiva', $avaliacao?->avaliacao_subjetiva ?? 'ABSTER') === 'ABSTER')>- Abster</option>
                        </select>
                    </div>
                </div>
                <div>
                    <x-input-label for="comentario" value="Comentário" />
                    <textarea id="comentario" name="comentario" rows="3" class="input-base">{{ old('comentario', $avaliacao?->comentario) }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn-success">Salvar avaliação</button>
                </div>
            </form>
        </section>

        @if ($isAprovadorNoEdital)
            <section class="panel-card space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Avaliações da banca</h3>
                    <p class="text-xs text-slate-500">Visualização disponível somente para docente aprovador deste edital.</p>
                </div>

                <div class="table-wrap">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Status</th>
                                <th>Nota</th>
                                <th>Avaliação Subjetiva</th>
                                <th>Comentário</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inscricao->edital->docentesBanca as $docenteBanca)
                                @php
                                    $avaliacaoDocente = $inscricao->avaliacoes->firstWhere('docente_id', $docenteBanca->id);
                                    $statusDocente = $avaliacaoDocente && $avaliacaoDocente->nota !== null ? 'AVALIADO' : 'PENDENTE';
                                @endphp
                                <tr>
                                    <td>{{ $docenteBanca->name }}</td>
                                    <td>
                                        <span class="status-badge {{ $statusDocente === 'AVALIADO' ? 'status-homologada' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $statusDocente }}
                                        </span>
                                    </td>
                                    <td>{{ $avaliacaoDocente && $avaliacaoDocente->nota !== null ? number_format((float) $avaliacaoDocente->nota, 2, ',', '.') : '-' }}</td>
                                    <td>{{ $avaliacaoDocente?->avaliacao_subjetiva ?: '-' }}</td>
                                    <td>{{ $avaliacaoDocente?->comentario ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-slate-500">Sem docentes na banca.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <h4 class="text-sm font-semibold text-slate-800">Veredito final da inscrição</h4>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('docente.inscricoes.status', $inscricao) }}">
                            @csrf
                            <input type="hidden" name="status" value="RECEBIDA">
                            <button type="submit" class="btn-muted {{ $inscricao->status === 'RECEBIDA' ? 'ring-2 ring-slate-400' : '' }}">Em Análise</button>
                        </form>
                        <form method="POST" action="{{ route('docente.inscricoes.status', $inscricao) }}">
                            @csrf
                            <input type="hidden" name="status" value="HOMOLOGADA">
                            <button type="submit" class="btn-success {{ $inscricao->status === 'HOMOLOGADA' ? 'ring-2 ring-emerald-500' : '' }}">Homologada</button>
                        </form>
                        <form method="POST" action="{{ route('docente.inscricoes.status', $inscricao) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="hidden" name="status" value="INDEFERIDA">
                            <input type="text" name="indeferimento_motivo" class="input-base w-72" placeholder="Motivo do indeferimento" required>
                            <button type="submit" class="btn-danger {{ $inscricao->status === 'INDEFERIDA' ? 'ring-2 ring-red-500' : '' }}">Indeferida</button>
                        </form>
                    </div>
                </div>
            </section>
        @endif

        <section class="panel-card">
            <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                <button type="button" @click="tab = 'dados'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'dados' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Dados</button>
                <button type="button" @click="tab = 'documentos'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'documentos' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Documentos</button>
            </div>

            <div x-show="tab === 'dados'" x-transition class="space-y-2 text-sm text-slate-700">
                <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
                <p><strong>Email:</strong> {{ $inscricao->email }}</p>
                <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
                <p><strong>Telefone:</strong> {{ $inscricao->telefone ?: '-' }}</p>
                <p><strong>Status final:</strong>
                    @php
                        $statusClass = match($inscricao->status) {
                            'HOMOLOGADA' => 'status-homologada',
                            'INDEFERIDA' => 'status-indeferida',
                            default => 'status-recebida',
                        };
                        $statusLabel = match($inscricao->status) {
                            'HOMOLOGADA' => 'Homologada',
                            'INDEFERIDA' => 'Indeferida',
                            default => 'Em Análise',
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </p>
            </div>

            <div x-show="tab === 'documentos'" x-transition>
                <ul class="space-y-2">
                    @forelse ($inscricao->documentos as $doc)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm">
                            <span>{{ $doc->tipo }} ({{ $doc->original_name }})</span>
                            <a href="{{ route('docente.inscricoes.documentos.download', [$inscricao, $doc]) }}" class="text-blue-600 hover:underline">Download</a>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Nenhum documento enviado.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <a href="{{ route('docente.inscricoes.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">Voltar para inscrições</a>
    </div>
</x-app-layout>
