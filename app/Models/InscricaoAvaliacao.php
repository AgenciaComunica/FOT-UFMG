<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscricaoAvaliacao extends Model
{
    use HasFactory;

    public const SUBJETIVA_HOMOLOGAR = 'HOMOLOGAR';
    public const SUBJETIVA_INDEFERIR = 'INDEFERIR';
    public const SUBJETIVA_ABSTER = 'ABSTER';

    protected $table = 'inscricao_avaliacoes';

    protected $fillable = [
        'inscricao_id',
        'docente_id',
        'nota',
        'avaliacao_subjetiva',
        'comentario',
        'avaliado_at',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
            'avaliado_at' => 'datetime',
        ];
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function getStatusAttribute(): string
    {
        return $this->nota === null ? 'PENDENTE' : 'AVALIADO';
    }
}
