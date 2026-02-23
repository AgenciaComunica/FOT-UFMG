<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditalDocumentoRequerido extends Model
{
    use HasFactory;

    protected $table = 'edital_documento_requeridos';

    protected $fillable = [
        'edital_id',
        'tipo',
        'descricao',
        'obrigatorio',
        'ordem',
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
}
