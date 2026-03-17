<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inscricao extends Model
{
    use HasFactory;

    protected $table = 'inscricoes';

    public const STATUS_RECEBIDA = 'RECEBIDA';
    public const STATUS_PRE_APROVADA = 'PRE_APROVADA';
    public const STATUS_PRE_INDEFERIDA = 'PRE_INDEFERIDA';
    public const STATUS_HOMOLOGADA = 'HOMOLOGADA';
    public const STATUS_INDEFERIDA = 'INDEFERIDA';

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_HOMOLOGADA => 'Homologada',
            self::STATUS_PRE_APROVADA => 'Classificada',
            self::STATUS_PRE_INDEFERIDA => 'Excedente',
            self::STATUS_INDEFERIDA => 'Não homologada',
            default => 'Em homologação',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_HOMOLOGADA => 'status-homologada',
            self::STATUS_PRE_APROVADA => 'bg-cyan-100 text-cyan-700',
            self::STATUS_PRE_INDEFERIDA => 'bg-orange-100 text-orange-700',
            self::STATUS_INDEFERIDA => 'status-indeferida',
            default => 'status-recebida',
        };
    }

    public function permiteEdicaoPublica(): bool
    {
        return in_array($this->status, [self::STATUS_RECEBIDA, self::STATUS_INDEFERIDA], true);
    }

    public function estaHomologada(): bool
    {
        return in_array($this->status, [self::STATUS_HOMOLOGADA, self::STATUS_PRE_APROVADA, self::STATUS_PRE_INDEFERIDA], true);
    }

    protected $fillable = [
        'edital_id',
        'user_id',
        'protocolo',
        'nome_completo',
        'email',
        'cpf',
        'telefone',
        'email_verification_token',
        'verification_sent_at',
        'email_verified_at',
        'resultado_email_sent_at',
        'edit_link_token',
        'edit_link_sent_at',
        'edit_link_expires_at',
        'edit_link_used_at',
        'status',
        'submitted_at',
        'decided_at',
        'decided_by',
        'indeferimento_motivo',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'resultado_email_sent_at' => 'datetime',
            'edit_link_sent_at' => 'datetime',
            'edit_link_expires_at' => 'datetime',
            'edit_link_used_at' => 'datetime',
        ];
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function edital(): BelongsTo
    {
        return $this->belongsTo(Edital::class);
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

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(InscricaoAvaliacao::class);
    }

    public function edicoes(): HasMany
    {
        return $this->hasMany(InscricaoEdicao::class);
    }

    public function ultimaEdicao(): HasOne
    {
        return $this->hasOne(InscricaoEdicao::class)->latestOfMany('edited_at');
    }

    public function possuiDocumentosObrigatorios(): bool
    {
        if (! $this->relationLoaded('edital')) {
            $this->load('edital.documentosRequeridos');
        }

        if (! $this->relationLoaded('documentos')) {
            $this->load('documentos');
        }

        if (! $this->edital) {
            return false;
        }

        $enviados = $this->documentos->pluck('tipo')->unique();

        foreach ($this->edital->documentosRequeridos->where('obrigatorio', true) as $requerido) {
            if (! $enviados->contains($requerido->tipo)) {
                return false;
            }
        }

        return true;
    }
}
