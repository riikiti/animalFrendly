<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PhoneVerificationCode extends Model
{
    use HasUlids;

    protected $table = 'phone_verification_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'phone',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
