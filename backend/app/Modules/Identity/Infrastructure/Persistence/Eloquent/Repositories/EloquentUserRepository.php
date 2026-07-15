<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Identity\Domain\Entities\User as DomainUser;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as EloquentUser;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function existsByPhone(PhoneNumber $phone): bool
    {
        return EloquentUser::query()->where('phone', $phone->value())->exists();
    }

    public function save(DomainUser $user): void
    {
        EloquentUser::query()->updateOrCreate(
            ['id' => $user->id()->toString()],
            [
                'phone' => $user->phone()->value(),
                'password_hash' => $user->passwordHash(),
                'account_type' => $user->accountType()->value,
                'personal_data_consent_at' => $user->personalDataConsentAt(),
                'status' => $user->status()->value,
            ],
        );
    }

    public function revokeAllTokens(Id $userId): void
    {
        EloquentUser::query()->find($userId->toString())?->tokens()->delete();
    }

    public function findById(Id $id): ?DomainUser
    {
        $model = EloquentUser::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByPhone(PhoneNumber $phone): ?DomainUser
    {
        $model = EloquentUser::query()->where('phone', $phone->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentUser $model): DomainUser
    {
        return DomainUser::reconstitute(
            id: Id::fromString($model->id),
            phone: PhoneNumber::fromString($model->phone),
            passwordHash: $model->password_hash,
            accountType: AccountType::from($model->account_type),
            personalDataConsentAt: $model->personal_data_consent_at->toDateTimeImmutable(),
            status: UserStatus::from($model->status),
        );
    }
}
