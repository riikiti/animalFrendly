<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\Identity\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'phone',
        'email',
        'password_hash',
        'account_type',
        'status',
        'phone_verified_at',
        'personal_data_consent_at',
        'address',
        'city',
        'latitude',
        'longitude',
        'name',
    ];

    protected $hidden = [
        'password_hash',
    ];

    // Классическое свойство $casts (а не метод casts()) — на момент написания лучше
    // поддерживается выводом типов Larastan для атрибутов Eloquent.
    protected $casts = [
        'phone_verified_at' => 'datetime',
        'personal_data_consent_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Guard аутентификации читает пароль отсюда — колонка называется password_hash,
     * см. docs/database/01-identity-profile.md.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
