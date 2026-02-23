<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edital extends Model
{
    use HasFactory;

    protected $table = 'editais';

    protected $fillable = [
        'titulo',
        'descricao',
        'periodo_inscricao_inicio',
        'periodo_inscricao_fim',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inscricao_inicio' => 'datetime',
            'periodo_inscricao_fim' => 'datetime',
        ];
    }

    public function documentosRequeridos(): HasMany
    {
        return $this->hasMany(EditalDocumentoRequerido::class)->orderBy('ordem');
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function getStatusAttribute(): string
    {
        $now = now();

        if ($now->lt($this->periodo_inscricao_inicio)) {
            return 'AGUARDANDO';
        }

        if ($now->gt($this->periodo_inscricao_fim)) {
            return 'ENCERRADO';
        }

        return 'ABERTO';
    }

    public function isAberto(): bool
    {
        return $this->status === 'ABERTO';
    }
}
