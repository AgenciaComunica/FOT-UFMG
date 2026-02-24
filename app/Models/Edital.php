<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edital extends Model
{
    use HasFactory;

    public const CORTE_FIXA = 'FIXA';
    public const CORTE_MEDIA_FLUTUANTE = 'MEDIA_FLUTUANTE';
    public const CORTE_NUMERO_VAGAS = 'NUMERO_VAGAS';
    public const CORTE_APROVACAO_MANUAL = 'APROVACAO_MANUAL';

    protected $table = 'editais';

    protected $fillable = [
        'titulo',
        'descricao',
        'publicado',
        'criterio_nota_corte',
        'nota_corte_fixa',
        'nota_corte_offset',
        'numero_vagas',
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
            'nota_corte_fixa' => 'decimal:2',
            'nota_corte_offset' => 'decimal:2',
            'numero_vagas' => 'integer',
            'periodo_inscricao_inicio' => 'datetime',
            'periodo_inscricao_fim' => 'datetime',
            'inscricoes_encerramento_notificado_at' => 'datetime',
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

    public function docentesBanca(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'edital_docentes')
            ->withPivot('ordem', 'aprovador')
            ->withTimestamps()
            ->orderBy('edital_docentes.ordem');
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
