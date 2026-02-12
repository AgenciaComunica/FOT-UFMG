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
    public const STATUS_APROVADA = 'APROVADA';
    public const STATUS_REJEITADA = 'REJEITADA';

    protected $fillable = [
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
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
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
}
