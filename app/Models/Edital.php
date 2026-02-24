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
        'publicado',
        'periodo_inscricao_inicio',
        'periodo_inscricao_fim',
        'arquivo_path',
        'arquivo_original_name',
        'arquivo_mime',
        'arquivo_size',
    ];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
            'periodo_inscricao_inicio' => 'datetime',
            'periodo_inscricao_fim' => 'datetime',
            'arquivo_size' => 'integer',
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
        if (! $this->publicado) {
            return 'RASCUNHO';
        }

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

    public function hasArquivoEdital(): bool
    {
        return filled($this->arquivo_path);
    }
}
