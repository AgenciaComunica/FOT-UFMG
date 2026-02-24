<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Inscrição {{ $inscricao->protocolo }}</h2>
                <p class="text-sm text-slate-500">{{ $inscricao->edital?->titulo }}</p>
            </div>
            <a href="{{ route('admin.inscricoes.index', ['edital_id' => $inscricao->edital_id]) }}" class="btn-muted">Voltar para Inscrições</a>
        </div>
    </x-slot>

    @php
        $avaliacoesJson = $avaliacoesPainel->map(function ($item) {
            return [
                'docente_id' => $item['docente']->id,
                'docente_nome' => $item['docente']->name,
                'status' => $item['status'],
                'nota' => $item['nota'] !== null ? (string) $item['nota'] : '',
                'avaliacao_subjetiva' => $item['avaliacao_subjetiva'] ?? '',
                'comentario' => $item['comentario'] ?? '',
            ];
        })->values();
    @endphp

    <div class="mx-auto w-full max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8"
         x-data="inscricaoAvaliacaoPage(@js($avaliacoesJson))">

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
            <div class="mb-4 flex items-start justify-between gap-3 border-b border-slate-200 pb-3">
                @php
                    $totalAvaliacoes = $avaliacoesPainel->count();
                    $avaliacoesConcluidas = $avaliacoesPainel->where('status', 'AVALIADO')->count();
                    $mediaBadgeClass = $avaliacoesConcluidas === 0
                        ? 'bg-red-50 text-red-700'
                        : ($avaliacoesConcluidas === $totalAvaliacoes
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'bg-amber-50 text-amber-700');
                @endphp
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="tab = 'dados'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'dados' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Dados</button>
                    <button type="button" @click="tab = 'documentos'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'documentos' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Documentos</button>
                    <button type="button" @click="tab = 'avaliacoes'" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="tab === 'avaliacoes' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'">Avaliações</button>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $mediaBadgeClass }}">
                    Média das Avaliações {{ $mediaAvaliacoes ?? '-' }}
                </span>
            </div>

            <div x-show="tab === 'dados'" x-transition class="space-y-2 text-sm text-slate-700">
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
                <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
                <p><strong>Email:</strong> {{ $inscricao->email }}</p>
                <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
                <p><strong>Telefone:</strong> {{ $inscricao->telefone ?: '-' }}</p>
                <p><strong>Status:</strong> <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></p>
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

            <div x-show="tab === 'avaliacoes'" x-transition class="space-y-4">
                <div class="table-wrap">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Status</th>
                                <th>Nota</th>
                                <th>Data última avaliação</th>
                                <th>Avaliação Subjetiva</th>
                                <th>Comentário</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($avaliacoesPainel as $item)
                                @php
                                    $statusClassAvaliacao = $item['status'] === 'AVALIADO'
                                        ? 'status-homologada'
                                        : 'bg-blue-100 text-blue-700';
                                @endphp
                                <tr>
                                    <td class="font-semibold text-slate-700">{{ $item['docente']->name }}</td>
                                    <td><span class="status-badge {{ $statusClassAvaliacao }}">{{ $item['status'] }}</span></td>
                                    <td>{{ $item['nota'] !== null ? number_format((float) $item['nota'], 2, ',', '.') : '-' }}</td>
                                    <td>{{ $item['ultima_avaliacao_at'] ? \Illuminate\Support\Carbon::parse($item['ultima_avaliacao_at'])->format('d/m/Y H:i') : '-' }}</td>
                                    <td>
                                        @if ($item['avaliacao_subjetiva'] === 'HOMOLOGAR')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">✓ Homologar</span>
                                        @elseif ($item['avaliacao_subjetiva'] === 'INDEFERIR')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">✕ Indeferir</span>
                                        @elseif ($item['avaliacao_subjetiva'] === 'ABSTER')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">- Abster</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $item['comentario'] ?: '-' }}</td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                                @click="openEdit({
                                                    docente_id: {{ $item['docente']->id }},
                                                    docente_nome: @js($item['docente']->name),
                                                    status: @js($item['status']),
                                                    nota: @js($item['nota'] !== null ? (string) $item['nota'] : ''),
                                                    avaliacao_subjetiva: @js($item['avaliacao_subjetiva'] ?? ''),
                                                    comentario: @js($item['comentario'] ?? '')
                                                })"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M17.414 2.586a2 2 0 010 2.828l-9.9 9.9a1 1 0 01-.46.263l-3.5.875a1 1 0 01-1.213-1.213l.875-3.5a1 1 0 01.263-.46l9.9-9.9a2 2 0 012.828 0z" />
                                                </svg>
                                                Editar
                                            </button>

                                            @if ($item['status'] === 'PENDENTE')
                                                <form method="POST" action="{{ route('admin.inscricoes.avaliacoes.lembrete', [$inscricao, $item['docente']]) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1.172c0 .53-.21 1.04-.586 1.414L4 10h12l-1.414-1.414A2 2 0 0114 7.172V6a4 4 0 00-4-4zM8 16a2 2 0 104 0H8z" clip-rule="evenodd" />
                                                        </svg>
                                                        Lembrete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-slate-500">Nenhum docente configurado na banca deste edital.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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

        <a href="{{ route('admin.inscricoes.index', ['edital_id' => $inscricao->edital_id]) }}" class="text-sm font-semibold text-blue-600 hover:underline">Voltar para listagem</a>

        <form x-ref="formSalvarAvaliacao" method="POST" action="{{ route('admin.inscricoes.avaliacoes.salvar', $inscricao) }}" class="hidden">
            @csrf
            <input type="hidden" name="docente_id" :value="avaliacaoForm.docente_id">
            <input type="hidden" name="nota" :value="avaliacaoForm.nota">
            <input type="hidden" name="avaliacao_subjetiva" :value="avaliacaoForm.avaliacao_subjetiva">
            <input type="hidden" name="comentario" :value="avaliacaoForm.comentario">
            <input type="hidden" name="confirm_code_expected" :value="confirmModal.codeExpected">
            <input type="hidden" name="confirm_code_input" :value="confirmModal.codeInput">
        </form>

        <form x-ref="formLimparAvaliacao" method="POST" action="{{ route('admin.inscricoes.avaliacoes.limpar', $inscricao) }}" class="hidden">
            @csrf
            <input type="hidden" name="docente_id" :value="avaliacaoForm.docente_id">
            <input type="hidden" name="confirm_code_expected" :value="confirmModal.codeExpected">
            <input type="hidden" name="confirm_code_input" :value="confirmModal.codeInput">
        </form>

        <div x-show="editModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeEditModal()">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Editar avaliação</h3>
                <p class="mt-1 text-sm text-slate-600">Docente: <strong x-text="avaliacaoForm.docente_nome"></strong></p>

                <div class="mt-4 space-y-3">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <x-input-label value="Nota (0 a 10)" />
                            <input type="number" min="0" max="10" step="0.01" class="input-base mt-1" x-model="avaliacaoForm.nota">
                        </div>
                        <div>
                            <x-input-label value="Avaliação Subjetiva" />
                            <select class="input-base mt-1" x-model="avaliacaoForm.avaliacao_subjetiva">
                                <option value="HOMOLOGAR">✓ Homologar</option>
                                <option value="INDEFERIR">✕ Indeferir</option>
                                <option value="ABSTER">- Abster</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Comentário" />
                        <textarea rows="4" class="input-base mt-1" x-model="avaliacaoForm.comentario"></textarea>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="btn-muted" @click="closeEditModal()">Cancelar</button>
                    <button type="button" class="btn-danger" @click="requestConfirm('limpar')">Limpar</button>
                    <button type="button" class="btn-success" @click="requestConfirm('salvar')">Salvar</button>
                </div>
            </div>
        </div>

        <div x-show="confirmModal.open" x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4" style="display: none;" @click.self="closeConfirmModal()">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">Confirmar ação</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Esta ação é irreversível e irá alterar a avaliação feita por um docente.
                </p>
                <p class="mt-3 text-sm text-slate-700">
                    Digite o código <strong x-text="confirmModal.codeExpected"></strong> para confirmar.
                </p>
                <input type="text" maxlength="2" class="input-base mt-2" x-model="confirmModal.codeInput" placeholder="Ex.: 42">
                <p class="mt-2 text-xs text-red-600" x-show="confirmModal.error" x-text="confirmModal.error"></p>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="btn-muted" @click="closeConfirmModal()">Cancelar</button>
                    <button type="button" class="btn-primary" @click="submitConfirmed()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function inscricaoAvaliacaoPage(initialAvaliacoes) {
            return {
                tab: 'dados',
                modalHomologar: false,
                modalIndeferir: false,
                editModal: { open: false },
                avaliacaoForm: {
                    docente_id: '',
                    docente_nome: '',
                    nota: '',
                    avaliacao_subjetiva: 'ABSTER',
                    comentario: '',
                },
                confirmModal: {
                    open: false,
                    action: null,
                    codeExpected: '',
                    codeInput: '',
                    error: '',
                },
                avaliacoes: Array.isArray(initialAvaliacoes) ? initialAvaliacoes : [],
                openEdit(item) {
                    this.avaliacaoForm.docente_id = String(item.docente_id ?? '');
                    this.avaliacaoForm.docente_nome = item.docente_nome ?? '';
                    this.avaliacaoForm.nota = item.nota ?? '';
                    this.avaliacaoForm.avaliacao_subjetiva = item.avaliacao_subjetiva ?? 'ABSTER';
                    this.avaliacaoForm.comentario = item.comentario ?? '';
                    this.editModal.open = true;
                },
                closeEditModal() {
                    this.editModal.open = false;
                },
                requestConfirm(action) {
                    this.confirmModal.action = action;
                    this.confirmModal.codeExpected = String(Math.floor(Math.random() * 90) + 10);
                    this.confirmModal.codeInput = '';
                    this.confirmModal.error = '';
                    this.confirmModal.open = true;
                },
                closeConfirmModal() {
                    this.confirmModal.open = false;
                    this.confirmModal.action = null;
                    this.confirmModal.error = '';
                    this.confirmModal.codeInput = '';
                },
                submitConfirmed() {
                    if (this.confirmModal.codeInput.trim() !== this.confirmModal.codeExpected.trim()) {
                        this.confirmModal.error = 'Código de confirmação inválido.';
                        return;
                    }

                    if (this.confirmModal.action === 'salvar') {
                        this.$refs.formSalvarAvaliacao.submit();
                        return;
                    }

                    if (this.confirmModal.action === 'limpar') {
                        this.$refs.formLimparAvaliacao.submit();
                    }
                },
            };
        }
    </script>
</x-app-layout>
