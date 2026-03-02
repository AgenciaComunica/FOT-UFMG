<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscricaoEdicao extends Model
{
    use HasFactory;

    protected $table = 'inscricao_edicoes';

    protected $fillable = [
        'inscricao_id',
        'motivo',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }
}

