<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Payment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'payable_type',
        'payable_id',
        'yookassa_payment_id',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}
