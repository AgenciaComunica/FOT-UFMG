<x-mail::message>
# {{ $tipoAviso === 'encerrando' ? 'Edital próximo de encerrar' : 'Novo edital disponível' }}

Olá, {{ $nome }}.

@if ($tipoAviso === 'encerrando')
O edital **{{ $edital->titulo }}** está com inscrições abertas e se encerra em **{{ optional($edital->periodo_inscricao_fim)->format('d/m/Y H:i') }}**.
@else
O edital **{{ $edital->titulo }}** está disponível para acompanhamento no portal.

@if ($edital->isAberto())
As inscrições ficam abertas até **{{ optional($edital->periodo_inscricao_fim)->format('d/m/Y H:i') }}**.
@endif
@endif

<x-mail::button :url="$editalUrl">
Ver edital
</x-mail::button>

Você também pode acompanhar todos os editais no portal público:

<x-mail::button :url="$portalUrl">
Abrir portal de editais
</x-mail::button>

Atenciosamente,<br>
{{ config('app.name') }}
</x-mail::message>
