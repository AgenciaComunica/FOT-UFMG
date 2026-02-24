<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscricaoAvaliacao extends Model
{
    use HasFactory;

    protected $table = 'inscricao_avaliacoes';

    protected $fillable = [
        'inscricao_id',
        'docente_id',
        'nota',
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
