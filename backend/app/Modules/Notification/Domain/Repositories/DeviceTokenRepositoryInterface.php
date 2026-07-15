<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Repositories;

use App\Modules\Notification\Domain\Entities\DeviceToken;
use App\Shared\Domain\ValueObjects\Id;

interface DeviceTokenRepositoryInterface
{
    public function nextIdentity(): Id;

    /**
     * Сохраняет токен, обновляя запись, если такой fcm_token уже зарегистрирован
     * (переустановка приложения/повторная регистрация тем же устройством).
     */
    public function save(DeviceToken $token): void;

    public function findByToken(string $fcmToken): ?DeviceToken;

    /**
     * @return list<DeviceToken>
     */
    public function findByUser(Id $userId): array;

    public function deleteByToken(string $fcmToken): void;
}
