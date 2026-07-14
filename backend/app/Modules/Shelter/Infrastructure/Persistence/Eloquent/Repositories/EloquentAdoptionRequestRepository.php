<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest as DomainAdoptionRequest;
use App\Modules\Shelter\Domain\Enums\AdoptionRequestStatus;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Models\AdoptionRequest as EloquentAdoptionRequest;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentAdoptionRequestRepository implements AdoptionRequestRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainAdoptionRequest $request): void
    {
        EloquentAdoptionRequest::query()->updateOrCreate(
            ['id' => $request->id()->toString()],
            [
                'shelter_animal_id' => $request->shelterAnimalId()->toString(),
                'requester_user_id' => $request->requesterUserId()->toString(),
                'status' => $request->status()->value,
                'message' => $request->message(),
                'decided_at' => $request->decidedAt(),
                'decided_by' => $request->decidedBy()?->toString(),
            ],
        );
    }

    public function findById(Id $id): ?DomainAdoptionRequest
    {
        $model = EloquentAdoptionRequest::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByShelterAnimal(Id $shelterAnimalId): array
    {
        return array_values(
            EloquentAdoptionRequest::query()
                ->where('shelter_animal_id', $shelterAnimalId->toString())
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findByRequester(Id $requesterUserId): array
    {
        return array_values(
            EloquentAdoptionRequest::query()
                ->where('requester_user_id', $requesterUserId->toString())
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findPendingForShelterAnimals(array $shelterAnimalIds): array
    {
        if ($shelterAnimalIds === []) {
            return [];
        }

        $idStrings = array_map(static fn (Id $id): string => $id->toString(), $shelterAnimalIds);

        return array_values(
            EloquentAdoptionRequest::query()
                ->whereIn('shelter_animal_id', $idStrings)
                ->where('status', 'pending')
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentAdoptionRequest $model): DomainAdoptionRequest
    {
        return DomainAdoptionRequest::reconstitute(
            id: Id::fromString($model->id),
            shelterAnimalId: Id::fromString($model->shelter_animal_id),
            requesterUserId: Id::fromString($model->requester_user_id),
            message: $model->message,
            status: AdoptionRequestStatus::from($model->status),
            decidedAt: $model->decided_at?->toDateTimeImmutable(),
            decidedBy: $model->decided_by !== null ? Id::fromString($model->decided_by) : null,
        );
    }
}
