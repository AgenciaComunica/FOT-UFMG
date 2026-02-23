<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscricao extends Model
{
    use HasFactory;

    protected $table = 'inscricoes';

    public const STATUS_RECEBIDA = 'RECEBIDA';
    public const STATUS_HOMOLOGADA = 'HOMOLOGADA';
    public const STATUS_INDEFERIDA = 'INDEFERIDA';

    protected $fillable = [
        'edital_id',
        'user_id',
        'protocolo',
        'nome_completo',
        'email',
        'cpf',
        'telefone',
        'status',
        'submitted_at',
        'decided_at',
        'decided_by',
        'indeferimento_motivo',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(InscricaoDocumento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function possuiDocumentosObrigatorios(): bool
    {
        if (! $this->relationLoaded('edital')) {
            $this->load('edital.documentosRequeridos');
        }

        if (! $this->relationLoaded('documentos')) {
            $this->load('documentos');
        }

        if (! $this->edital) {
            return false;
        }

        $enviados = $this->documentos->pluck('tipo')->unique();

        foreach ($this->edital->documentosRequeridos->where('obrigatorio', true) as $requerido) {
            if (! $enviados->contains($requerido->tipo)) {
                return false;
            }
        }

        return true;
    }
}
