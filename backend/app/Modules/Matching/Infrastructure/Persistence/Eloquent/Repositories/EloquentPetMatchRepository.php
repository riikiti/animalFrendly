<?php

declare(strict_types=1);

namespace App\Modules\Matching\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Matching\Domain\Entities\PetMatch as DomainPetMatch;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Matching\Infrastructure\Persistence\Eloquent\Models\PetMatch as EloquentPetMatch;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Database\Eloquent\Builder;

final class EloquentPetMatchRepository implements PetMatchRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainPetMatch $match): void
    {
        EloquentPetMatch::query()->updateOrCreate(
            ['id' => $match->id()->toString()],
            [
                'pet_a_id' => $match->petAId()->toString(),
                'pet_b_id' => $match->petBId()->toString(),
                'matched_at' => $match->matchedAt(),
                'unmatched_at' => $match->unmatchedAt(),
            ],
        );
    }

    public function existsBetween(Id $petOneId, Id $petTwoId): bool
    {
        [$petAId, $petBId] = DomainPetMatch::ordered($petOneId, $petTwoId);

        return EloquentPetMatch::query()
            ->where('pet_a_id', $petAId->toString())
            ->where('pet_b_id', $petBId->toString())
            ->exists();
    }

    public function findForPet(Id $petId): array
    {
        return array_values(
            EloquentPetMatch::query()
                ->where(function (Builder $query) use ($petId): void {
                    $query->where('pet_a_id', $petId->toString())
                        ->orWhere('pet_b_id', $petId->toString());
                })
                ->orderByDesc('matched_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findById(Id $id): ?DomainPetMatch
    {
        $model = EloquentPetMatch::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentPetMatch $model): DomainPetMatch
    {
        return DomainPetMatch::reconstitute(
            id: Id::fromString($model->id),
            petAId: Id::fromString($model->pet_a_id),
            petBId: Id::fromString($model->pet_b_id),
            matchedAt: $model->matched_at->toDateTimeImmutable(),
            unmatchedAt: $model->unmatched_at?->toDateTimeImmutable(),
        );
    }
}
