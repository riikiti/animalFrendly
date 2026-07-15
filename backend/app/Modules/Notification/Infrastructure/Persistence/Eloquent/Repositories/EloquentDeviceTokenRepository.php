<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Notification\Domain\Entities\DeviceToken as DomainDeviceToken;
use App\Modules\Notification\Domain\Enums\DevicePlatform;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\DeviceToken as EloquentDeviceToken;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentDeviceTokenRepository implements DeviceTokenRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainDeviceToken $token): void
    {
        EloquentDeviceToken::query()->updateOrCreate(
            ['id' => $token->id()->toString()],
            [
                'user_id' => $token->userId()->toString(),
                'platform' => $token->platform()->value,
                'fcm_token' => $token->fcmToken(),
                'created_at' => $token->createdAt(),
                'last_used_at' => $token->lastUsedAt(),
            ],
        );
    }

    public function findByToken(string $fcmToken): ?DomainDeviceToken
    {
        $model = EloquentDeviceToken::query()->where('fcm_token', $fcmToken)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUser(Id $userId): array
    {
        return array_values(
            EloquentDeviceToken::query()
                ->where('user_id', $userId->toString())
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function deleteByToken(string $fcmToken): void
    {
        EloquentDeviceToken::query()->where('fcm_token', $fcmToken)->delete();
    }

    private function toDomain(EloquentDeviceToken $model): DomainDeviceToken
    {
        return DomainDeviceToken::reconstitute(
            id: Id::fromString($model->id),
            userId: Id::fromString($model->user_id),
            platform: DevicePlatform::from($model->platform),
            fcmToken: $model->fcm_token,
            createdAt: $model->created_at->toDateTimeImmutable(),
            lastUsedAt: $model->last_used_at->toDateTimeImmutable(),
        );
    }
}
