<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Services;

use App\Modules\Identity\Application\Contracts\SmsSenderInterface;
use App\Modules\Identity\Domain\Enums\PhoneCodePurpose;
use App\Modules\Identity\Domain\Exceptions\InvalidPhoneCodeException;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\PhoneVerificationCode;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Carbon;

/**
 * Одноразовые коды из СМС: выпуск, отправка и разовая проверка.
 * Один механизм обслуживает и вход по коду, и восстановление пароля — различает их
 * purpose, поэтому код от одного сценария не подойдёт другому.
 */
final class PhoneCodeService
{
    private const LENGTH = 4;

    private const TTL_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly SmsSenderInterface $sms,
        private readonly Hasher $hasher,
    ) {}

    /**
     * Выпускает код и отправляет его. Прежние невостребованные коды на тот же сценарий
     * гасим, чтобы работал ровно последний.
     */
    public function issue(PhoneNumber $phone, PhoneCodePurpose $purpose): void
    {
        PhoneVerificationCode::query()
            ->where('phone', $phone->value())
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => Carbon::now()]);

        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);

        PhoneVerificationCode::query()->create([
            'phone' => $phone->value(),
            'purpose' => $purpose->value,
            'code_hash' => $this->hasher->make($code),
            'expires_at' => Carbon::now()->addMinutes(self::TTL_MINUTES),
        ]);

        $this->sms->send($phone, "AnimalFriendly: код {$code}. Никому его не сообщайте.");
    }

    /**
     * Проверяет код и гасит его. Второй раз тот же код не сработает.
     *
     * @throws InvalidPhoneCodeException
     */
    public function consume(PhoneNumber $phone, PhoneCodePurpose $purpose, string $code): void
    {
        $record = PhoneVerificationCode::query()
            ->where('phone', $phone->value())
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('created_at')
            ->first();

        if ($record === null) {
            throw InvalidPhoneCodeException::create();
        }

        // Перебор четырёхзначного кода отсекаем счётчиком попыток, а не только сроком жизни.
        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->update(['consumed_at' => Carbon::now()]);

            throw InvalidPhoneCodeException::create();
        }

        if (! $this->hasher->check($code, $record->code_hash)) {
            $record->increment('attempts');

            throw InvalidPhoneCodeException::create();
        }

        $record->update(['consumed_at' => Carbon::now()]);
    }
}
