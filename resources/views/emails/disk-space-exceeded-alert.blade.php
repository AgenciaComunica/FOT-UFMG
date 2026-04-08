<p>Foi identificado limite crítico de espaço em disco no portal de inscrições.</p>

<p><strong>Contexto:</strong> {{ $contexto }}</p>
<p><strong>Espaço livre estimado:</strong> {{ $freeMb !== null ? $freeMb.' MB' : 'não identificado' }}</p>
<p><strong>Limite mínimo configurado:</strong> {{ $thresholdMb }} MB</p>

<p>Novos envios de documentos foram bloqueados automaticamente para evitar falhas e corrupção de dados.</p>
<p>Arquive editais encerrados ou libere espaço em disco antes de retomar as inscrições.</p>
