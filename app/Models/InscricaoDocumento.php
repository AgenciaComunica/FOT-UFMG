<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscricaoDocumento extends Model
{
    use HasFactory;

    public const DOCUMENTO_FOTO = 'DOCUMENTO_FOTO';
    public const COMPROVANTE_TAXA = 'COMPROVANTE_TAXA';
    public const DIPLOMA = 'DIPLOMA';
    public const HISTORICO_ESCOLAR = 'HISTORICO_ESCOLAR';
    public const CURRICULO = 'CURRICULO';

    public const TIPOS = [
        self::DOCUMENTO_FOTO,
        self::COMPROVANTE_TAXA,
        self::DIPLOMA,
        self::HISTORICO_ESCOLAR,
        self::CURRICULO,
    ];

    protected $fillable = [
        'inscricao_id',
        'tipo',
        'arquivo_path',
        'original_name',
        'mime',
        'size',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(Inscricao::class);
    }
}
