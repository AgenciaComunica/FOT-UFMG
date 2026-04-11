<x-app-layout>
    <style>[x-cloak]{display:none!important;}</style>
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
        $tabInitial = in_array(request('tab'), ['dados', 'documentos', 'avaliacoes'], true)
            ? request('tab')
            : 'dados';
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
         x-data="inscricaoAvaliacaoPage(@js($avaliacoesJson), @js($tabInitial))">

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

            <div x-show="tab === 'dados'" x-transition>
                @php
                    $statusClass = \App\Models\Inscricao::statusBadgeClass($inscricao->status);
                    $statusLabel = \App\Models\Inscricao::statusLabel($inscricao->status);
                @endphp
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-800">Dados da inscrição</h3>
                        <button type="button" class="btn-primary" @click="modalDados = true">Editar</button>
                    </div>
                    <div class="grid gap-2 text-sm text-slate-700 md:grid-cols-2">
                        <p><strong>Nome:</strong> {{ $inscricao->nome_completo }}</p>
                        <p><strong>Email:</strong> {{ $inscricao->email }}</p>
                        <p>
                            <strong>Email verificado:</strong>
                            @if ($inscricao->email_verified_at)
                                <span class="status-badge status-homologada">Verificado</span>
                            @else
                                <span class="status-badge status-indeferida">Não verificado</span>
                            @endif
                        </p>
                        <p><strong>CPF:</strong> {{ $inscricao->cpf }}</p>
                        <p><strong>Telefone:</strong> {{ $inscricao->telefone ?: '-' }}</p>
                        <p><strong>Status:</strong> <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></p>
                        <p><strong>Enviado em:</strong> {{ optional($inscricao->submitted_at)->format('d/m/Y H:i') }}</p>
                        <p><strong>Última edição:</strong> {{ optional($inscricao->ultimaEdicao?->edited_at)->format('d/m/Y H:i') ?: '-' }}</p>
                        <p><strong>Decidido em:</strong> {{ optional($inscricao->decided_at)->format('d/m/Y H:i') ?: '-' }}</p>
                        <p><strong>Decidido por:</strong> {{ optional($inscricao->decidedByUser)->name ?: '-' }}</p>
                        <p><strong>Motivo da não homologação:</strong> {{ $inscricao->indeferimento_motivo ?: '-' }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-800">Histórico de edições</h3>
                    @if ($inscricao->edicoes->isEmpty())
                        <p class="mt-2 text-sm text-slate-500">Nenhuma edição registrada pelo candidato.</p>
                    @else
                        <ul class="mt-2 space-y-2">
                            @foreach ($inscricao->edicoes as $edicao)
                                <li class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                    <p><strong>Data:</strong> {{ optional($edicao->edited_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                    <p class="mt-1"><strong>Motivo:</strong> {{ $edicao->motivo }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
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

                @if (! $inscricao->edital?->isArquivado())
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-800">Arquivos enviados</h3>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500">Use editar para substituir ou excluir arquivo</span>
                                <button type="button" class="btn-primary !px-3 !py-1.5 !text-xs" @click="openDocCreate()">Adicionar documento</button>
                            </div>
                        </div>
                        <ul class="mt-2 space-y-2">
                            @forelse ($inscricao->documentos as $doc)
                                <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                                    <span>{{ $doc->tipo }} ({{ $doc->original_name }})</span>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.inscricoes.documentos.download', [$inscricao, $doc]) }}" class="text-blue-600 hover:underline">Download</a>
                                        <button type="button"
                                                class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                                @click="openDocModal({{ $doc->id }}, @js($doc->tipo), @js($doc->original_name))">
                                            Editar
                                        </button>
                                        <button type="button"
                                                class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                @click="openDocDelete({{ $doc->id }}, @js($doc->tipo), @js($doc->original_name))">
                                            Excluir
                                        </button>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-lg border border-dashed border-slate-300 p-3 text-sm text-slate-500">
                                    Nenhum documento enviado.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                @endif
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

        <div class="panel-card">
            <h3 class="text-sm font-semibold text-slate-800">Fluxo da inscrição</h3>
            <p class="mt-1 text-xs text-slate-500">Toda alteração envia e-mail ao candidato. Primeiro homologue a inscrição; depois defina o resultado final.</p>
            @php
                $emHomologacao = in_array($inscricao->status, [\App\Models\Inscricao::STATUS_RECEBIDA, \App\Models\Inscricao::STATUS_INDEFERIDA], true);
                $homologada = $inscricao->status === \App\Models\Inscricao::STATUS_HOMOLOGADA;
                $finalizada = in_array($inscricao->status, [\App\Models\Inscricao::STATUS_PRE_APROVADA, \App\Models\Inscricao::STATUS_PRE_INDEFERIDA], true);
            @endphp
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($emHomologacao)
                    <button type="button" class="btn-success" @click="openStatusModal('HOMOLOGADA', 'Homologar inscrição', false)">Homologar</button>
                    <button type="button" class="btn-danger" @click="openStatusModal('INDEFERIDA', 'Não homologar inscrição', true)">Não homologar</button>
                @elseif ($homologada)
                    <button type="button" class="rounded-md bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-700" @click="openStatusModal('PRE_APROVADA', 'Classificar inscrição', false)">Classificar</button>
                    <button type="button" class="rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600" @click="openStatusModal('PRE_INDEFERIDA', 'Colocar em excedente', false)">Colocar em Excedente</button>
                    <button type="button" class="btn-danger" @click="openStatusModal('INDEFERIDA', 'Não homologar inscrição', true)">Não homologar</button>
                @elseif ($finalizada)
                    <button type="button" class="btn-muted" @click="openStatusModal('HOMOLOGADA', 'Voltar à análise', false)">Voltar à Análise</button>
                @endif
            </div>
        </div>

        <div x-cloak x-show="statusModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeStatusModal()">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900" x-text="statusModal.title"></h3>
                <form method="POST" action="{{ route('admin.inscricoes.status', $inscricao) }}" class="mt-3 space-y-3">
                    @csrf
                    <input type="hidden" name="status" :value="statusModal.status">
                    <p class="text-sm text-slate-600" x-text="statusModalMessage()"></p>
                    <div x-show="statusModal.requiresReason">
                        <x-input-label for="indeferimento_motivo" value="Motivo da não homologação (obrigatório)" />
                        <textarea id="indeferimento_motivo" name="indeferimento_motivo" rows="4" class="input-base" :required="statusModal.requiresReason">{{ old('indeferimento_motivo', $inscricao->indeferimento_motivo) }}</textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-muted" @click="closeStatusModal()">Cancelar</button>
                        <button type="submit" class="btn-primary">Confirmar ação</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modalDados" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="modalDados = false">
            <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Editar dados da inscrição</h3>
                <form method="POST" action="{{ route('admin.inscricoes.update', $inscricao) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input-label for="nome_completo" value="Nome completo" />
                            <x-text-input id="nome_completo" name="nome_completo" type="text" class="input-base mt-1" :value="old('nome_completo', $inscricao->nome_completo)" required />
                        </div>
                        <div>
                            <x-input-label for="email" value="E-mail" />
                            <x-text-input id="email" name="email" type="email" class="input-base mt-1" :value="old('email', $inscricao->email)" required />
                        </div>
                        <div class="md:col-span-2">
                            <label for="email_confirmado" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input
                                    id="email_confirmado"
                                    name="email_confirmado"
                                    type="checkbox"
                                    value="1"
                                    class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    @checked((bool) old('email_confirmado', $inscricao->email_verified_at !== null))
                                >
                                E-mail confirmado manualmente pela secretaria
                            </label>
                        </div>
                        <div>
                            <x-input-label for="cpf" value="CPF" />
                            <x-text-input id="cpf" name="cpf" type="text" class="input-base mt-1" :value="old('cpf', $inscricao->cpf)" required />
                        </div>
                        <div class="md:col-span-2">
                            <x-input-label for="telefone" value="Telefone" />
                            <x-text-input id="telefone" name="telefone" type="text" class="input-base mt-1" :value="old('telefone', $inscricao->telefone)" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-muted" @click="modalDados = false">Cancelar</button>
                        <button type="submit" class="btn-primary">Salvar dados</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="docCreateModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeDocCreate()">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Adicionar documento</h3>
                <form method="POST" action="{{ route('admin.inscricoes.documentos.store', $inscricao) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="novo_doc_tipo" value="Tipo do documento" />
                        <select id="novo_doc_tipo" name="tipo" class="input-base mt-1" required>
                            <option value="">Selecione</option>
                            @php
                                $tiposDocumento = $inscricao->edital->documentosRequeridos
                                    ->pluck('tipo')
                                    ->merge($inscricao->documentos->pluck('tipo'))
                                    ->push('OUTRO_DOCUMENTO')
                                    ->unique()
                                    ->values();
                            @endphp
                            @foreach ($tiposDocumento as $tipoDocumento)
                                <option value="{{ $tipoDocumento }}">{{ $tipoDocumento }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="novo_doc_arquivo" value="Arquivo" />
                        <input id="novo_doc_arquivo" name="arquivo" type="file" class="input-base mt-1" required>
                        <p class="mt-1 text-xs text-slate-500">Os formatos permitidos seguem a configuração do edital para o tipo selecionado.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-muted" @click="closeDocCreate()">Cancelar</button>
                        <button type="submit" class="btn-primary">Enviar documento</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="docModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeDocModal()">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Editar documento</h3>
                <p class="mt-1 text-sm text-slate-600">
                    <strong x-text="docModal.tipo"></strong>
                    <span class="text-slate-400">·</span>
                    <span x-text="docModal.originalName"></span>
                </p>
                <form method="POST" :action="docModal.updateUrl" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label value="Novo arquivo" />
                        <input name="arquivo" type="file" class="input-base mt-1" required>
                        <p class="mt-1 text-xs text-slate-500">Selecione o novo arquivo para substituir o atual.</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-muted" @click="closeDocModal()">Cancelar</button>
                        <button type="submit" class="btn-primary">Substituir arquivo</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="docDeleteModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeDocDelete()">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Excluir documento</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Deseja realmente excluir o documento
                    <strong x-text="docDeleteModal.tipo"></strong>
                    (<span x-text="docDeleteModal.originalName"></span>)?
                </p>
                <form method="POST" :action="docDeleteModal.deleteUrl" class="mt-4 flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn-muted" @click="closeDocDelete()">Cancelar</button>
                    <button type="submit" class="btn-danger">Excluir</button>
                </form>
            </div>
        </div>

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

        <div x-cloak x-show="editModal.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" style="display: none;" @click.self="closeEditModal()">
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

        <div x-cloak x-show="confirmModal.open" x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4" style="display: none;" @click.self="closeConfirmModal()">
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
        function inscricaoAvaliacaoPage(initialAvaliacoes, initialTab) {
            return {
                tab: initialTab || 'dados',
                statusModal: {
                    open: false,
                    status: '',
                    title: '',
                    requiresReason: false,
                },
                modalDados: false,
                editModal: { open: false },
                docModal: {
                    open: false,
                    docId: null,
                    tipo: '',
                    originalName: '',
                    updateUrl: '',
                },
                docCreateModal: {
                    open: false,
                },
                docDeleteModal: {
                    open: false,
                    docId: null,
                    tipo: '',
                    originalName: '',
                    deleteUrl: '',
                },
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
                docUpdateUrlTemplate: @js(route('admin.inscricoes.documentos.update', ['inscricao' => $inscricao, 'doc' => '__DOC__'])),
                docDeleteUrlTemplate: @js(route('admin.inscricoes.documentos.destroy', ['inscricao' => $inscricao, 'doc' => '__DOC__'])),
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
                openDocModal(docId, tipo, originalName) {
                    this.docModal.docId = docId;
                    this.docModal.tipo = tipo || '';
                    this.docModal.originalName = originalName || '';
                    this.docModal.updateUrl = this.docUpdateUrlTemplate.replace('__DOC__', String(docId));
                    this.docModal.open = true;
                },
                closeDocModal() {
                    this.docModal.open = false;
                    this.docModal.updateUrl = '';
                },
                openDocCreate() {
                    this.docCreateModal.open = true;
                },
                closeDocCreate() {
                    this.docCreateModal.open = false;
                },
                openDocDelete(docId, tipo, originalName) {
                    this.docDeleteModal.docId = docId;
                    this.docDeleteModal.tipo = tipo || '';
                    this.docDeleteModal.originalName = originalName || '';
                    this.docDeleteModal.deleteUrl = this.docDeleteUrlTemplate.replace('__DOC__', String(docId));
                    this.docDeleteModal.open = true;
                },
                closeDocDelete() {
                    this.docDeleteModal.open = false;
                    this.docDeleteModal.deleteUrl = '';
                },
                openStatusModal(status, title, requiresReason) {
                    this.statusModal.open = true;
                    this.statusModal.status = status;
                    this.statusModal.title = title;
                    this.statusModal.requiresReason = !!requiresReason;
                },
                closeStatusModal() {
                    this.statusModal.open = false;
                    this.statusModal.status = '';
                    this.statusModal.title = '';
                    this.statusModal.requiresReason = false;
                },
                statusModalMessage() {
                    if ([ 'HOMOLOGADA', 'INDEFERIDA', 'PRE_APROVADA', 'PRE_INDEFERIDA' ].includes(this.statusModal.status)) {
                        return 'Confirme a ação. O sistema enviará e-mail ao candidato após a atualização.';
                    }

                    return 'Confirme a ação para atualizar o status da inscrição.';
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
