<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditalDocumentoRequerido extends Model
{
    use HasFactory;

    public const FORMATOS_PERMITIDOS = ['pdf', 'docx', 'jpg', 'png'];

    protected $table = 'edital_documento_requeridos';

    protected $fillable = [
        'edital_id',
        'tipo',
        'formato_aceito',
        'descricao',
        'obrigatorio',
        'ordem',
    ];

    protected $appends = [
        'formatos_aceitos',
    ];

    protected function casts(): array
    {
        return [
            'obrigatorio' => 'boolean',
        ];
    }

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class);
    }

    public function getFormatosAceitosAttribute(): array
    {
        return collect(explode(',', (string) $this->formato_aceito))
            ->map(fn (string $ext) => strtolower(trim($ext)))
            ->filter(fn (string $ext) => in_array($ext, self::FORMATOS_PERMITIDOS, true))
            ->values()
            ->all();
    }
}
