<?php

namespace App\Models;

use App\Notifications\ResetPasswordPtBrNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SECRETARIA = self::ROLE_ADMIN;
    public const ROLE_ALUNO = 'aluno';
    public const ROLE_DOCENTE = 'docente';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefone',
        'password',
        'role',
        'ativo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function inscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class);
    }

    public function decisoesInscricoes(): HasMany
    {
        return $this->hasMany(Inscricao::class, 'decided_by');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordPtBrNotification($token));
    }
}
