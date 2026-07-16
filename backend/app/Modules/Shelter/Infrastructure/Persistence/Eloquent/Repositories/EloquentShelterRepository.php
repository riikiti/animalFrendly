<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shelter\Domain\Entities\Shelter as DomainShelter;
use App\Modules\Shelter\Domain\Enums\ShelterVerificationStatus;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Models\Shelter as EloquentShelter;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentShelterRepository implements ShelterRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainShelter $shelter): void
    {
        EloquentShelter::query()->updateOrCreate(
            ['id' => $shelter->id()->toString()],
            [
                'owner_user_id' => $shelter->ownerUserId()->toString(),
                'legal_name' => $shelter->legalName(),
                'inn' => $shelter->inn(),
                'description' => $shelter->description(),
                'verification_status' => $shelter->verificationStatus()->value,
                'verified_at' => $shelter->verifiedAt(),
                'verified_by' => $shelter->verifiedBy()?->toString(),
                'address' => $shelter->address(),
                'city' => $shelter->city(),
                'latitude' => $shelter->latitude(),
                'longitude' => $shelter->longitude(),
                'phone' => $shelter->phone(),
                'email' => $shelter->email(),
                'photo_url' => $shelter->photoUrl(),
            ],
        );
    }

    public function findById(Id $id): ?DomainShelter
    {
        $model = EloquentShelter::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOwnerUserId(Id $ownerUserId): array
    {
        return array_values(
            EloquentShelter::query()
                ->where('owner_user_id', $ownerUserId->toString())
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function countPendingVerification(): int
    {
        return EloquentShelter::query()->where('verification_status', ShelterVerificationStatus::Pending->value)->count();
    }

    public function findPendingVerification(): array
    {
        return array_values(
            EloquentShelter::query()
                ->where('verification_status', ShelterVerificationStatus::Pending->value)
                ->orderBy('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findVerified(): array
    {
        return array_values(
            EloquentShelter::query()
                ->where('verification_status', ShelterVerificationStatus::Verified->value)
                ->orderBy('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentShelter $model): DomainShelter
    {
        return DomainShelter::reconstitute(
            id: Id::fromString($model->id),
            ownerUserId: Id::fromString($model->owner_user_id),
            legalName: $model->legal_name,
            inn: $model->inn,
            description: $model->description,
            verificationStatus: ShelterVerificationStatus::from($model->verification_status),
            verifiedAt: $model->verified_at?->toDateTimeImmutable(),
            verifiedBy: $model->verified_by !== null ? Id::fromString($model->verified_by) : null,
            address: $model->address,
            city: $model->city,
            latitude: $model->latitude,
            longitude: $model->longitude,
            phone: $model->phone,
            email: $model->email,
            photoUrl: $model->photo_url,
        );
    }
}
