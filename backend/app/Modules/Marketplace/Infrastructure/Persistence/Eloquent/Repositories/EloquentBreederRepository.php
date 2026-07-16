<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Marketplace\Domain\Entities\Breeder as DomainBreeder;
use App\Modules\Marketplace\Domain\Enums\BreederVerificationStatus;
use App\Modules\Marketplace\Domain\Repositories\BreederRepositoryInterface;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models\Breeder as EloquentBreeder;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentBreederRepository implements BreederRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainBreeder $breeder): void
    {
        EloquentBreeder::query()->updateOrCreate(
            ['id' => $breeder->id()->toString()],
            [
                'owner_user_id' => $breeder->ownerUserId()->toString(),
                'verification_status' => $breeder->verificationStatus()->value,
                'verified_at' => $breeder->verifiedAt(),
                'verified_by' => $breeder->verifiedBy()?->toString(),
            ],
        );
    }

    public function findById(Id $id): ?DomainBreeder
    {
        $model = EloquentBreeder::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOwnerUserId(Id $ownerUserId): ?DomainBreeder
    {
        $model = EloquentBreeder::query()->where('owner_user_id', $ownerUserId->toString())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function countPendingVerification(): int
    {
        return EloquentBreeder::query()->where('verification_status', BreederVerificationStatus::Pending->value)->count();
    }

    public function findPendingVerification(): array
    {
        return array_values(
            EloquentBreeder::query()
                ->where('verification_status', BreederVerificationStatus::Pending->value)
                ->orderBy('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentBreeder $model): DomainBreeder
    {
        return DomainBreeder::reconstitute(
            id: Id::fromString($model->id),
            ownerUserId: Id::fromString($model->owner_user_id),
            verificationStatus: BreederVerificationStatus::from($model->verification_status),
            verifiedAt: $model->verified_at?->toDateTimeImmutable(),
            verifiedBy: $model->verified_by !== null ? Id::fromString($model->verified_by) : null,
        );
    }
}
