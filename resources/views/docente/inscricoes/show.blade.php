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

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8" x-data="{ tab: 'dados', statusModal: { open: false, status: '', title: '', requiresReason: false }, openStatusModal(status, title, requiresReason = false) { this.statusModal = { open: true, status, title, requiresReason }; }, closeStatusModal() { this.statusModal = { open: false, status: '', title: '', requiresReason: false }; } }">
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

        <section class="panel-card">
            <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                <button type="button" @click="tab = 'dados'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'dados' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Dados</button>
                <button type="button" @click="tab = 'documentos'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'documentos' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Documentos</button>
                @if ($isAprovadorNoEdital)
                    <button type="button" @click="tab = 'avaliacoes'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'avaliacoes' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Avaliações da banca</button>
                @endif
            </div>

            <div x-show="tab === 'dados'" x-transition class="space-y-2 text-sm text-slate-700">
                <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
                <p><strong>Email:</strong> {{ $inscricao->email }}</p>
                <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
                <p><strong>Telefone:</strong> {{ $inscricao->telefone ?: '-' }}</p>
                <p><strong>Status final:</strong>
                    @php
                        $statusClass = \App\Models\Inscricao::statusBadgeClass($inscricao->status);
                        $statusLabel = \App\Models\Inscricao::statusLabel($inscricao->status);
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

            @if ($isAprovadorNoEdital)
                <div x-show="tab === 'avaliacoes'" x-transition class="space-y-4">
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
                </div>
            @endif
        </section>

        @if ($isAprovadorNoEdital)
            <section class="panel-card">
                <h4 class="text-sm font-semibold text-slate-800">Veredito final da inscrição</h4>
                @php
                    $emHomologacaoDocente = in_array($inscricao->status, ['RECEBIDA', 'INDEFERIDA'], true);
                    $homologadaDocente = $inscricao->status === 'HOMOLOGADA';
                    $finalizadaDocente = in_array($inscricao->status, ['PRE_APROVADA', 'PRE_INDEFERIDA'], true);
                @endphp
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($emHomologacaoDocente)
                        <button type="button" class="btn-success" @click="openStatusModal('HOMOLOGADA', 'Homologar inscrição')">Homologar</button>
                        <button type="button" class="btn-danger" @click="openStatusModal('INDEFERIDA', 'Não homologar inscrição', true)">Não homologar</button>
                    @elseif ($homologadaDocente)
                        <button type="button" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700" @click="openStatusModal('PRE_APROVADA', 'Classificar inscrição')">Classificar</button>
                        <button type="button" class="rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600" @click="openStatusModal('PRE_INDEFERIDA', 'Colocar em excedente')">Colocar em Excedente</button>
                        <button type="button" class="btn-danger" @click="openStatusModal('INDEFERIDA', 'Não homologar inscrição', true)">Não homologar</button>
                    @elseif ($finalizadaDocente)
                        <button type="button" class="btn-muted" @click="openStatusModal('HOMOLOGADA', 'Voltar à análise')">Voltar à Análise</button>
                    @endif
                </div>
            </section>
        @endif

        @if ($isAprovadorNoEdital)
            <div x-show="statusModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeStatusModal()">
                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-900" x-text="statusModal.title"></h3>
                    <form method="POST" action="{{ route('docente.inscricoes.status', $inscricao) }}" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="status" :value="statusModal.status">
                        <p class="text-sm text-slate-600">Confirme a ação. O sistema enviará e-mail ao candidato após a atualização.</p>
                        <div x-show="statusModal.requiresReason">
                            <x-input-label for="indeferimento_motivo_docente" value="Motivo da não homologação (obrigatório)" />
                            <textarea id="indeferimento_motivo_docente" name="indeferimento_motivo" rows="4" class="input-base" :required="statusModal.requiresReason">{{ old('indeferimento_motivo', $inscricao->indeferimento_motivo) }}</textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-muted" @click="closeStatusModal()">Cancelar</button>
                            <button type="submit" class="btn-primary">Confirmar ação</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <a href="{{ route('docente.inscricoes.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">Voltar para inscrições</a>
    </div>
</x-app-layout>
