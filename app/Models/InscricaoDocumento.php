<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscricaoDocumento extends Model
{
    use HasFactory;

    public const DOCUMENTO_FOTO = 'DOCUMENTO_FOTO';
    public const DIPLOMA = 'DIPLOMA';
    public const CURRICULO = 'CURRICULO';
    public const COMPROVANTES_CURRICULO = 'COMPROVANTES_CURRICULO';
    public const HISTORICO_ESCOLAR = 'HISTORICO_ESCOLAR';

    public const TIPOS = [
        self::DOCUMENTO_FOTO,
        self::DIPLOMA,
        self::CURRICULO,
        self::COMPROVANTES_CURRICULO,
        self::HISTORICO_ESCOLAR,
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
