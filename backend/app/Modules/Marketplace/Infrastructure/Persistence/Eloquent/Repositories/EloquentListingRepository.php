<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Marketplace\Domain\Entities\Listing as DomainListing;
use App\Modules\Marketplace\Domain\Enums\ListingStatus;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models\Listing as EloquentListing;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class EloquentListingRepository implements ListingRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainListing $listing): void
    {
        EloquentListing::query()->updateOrCreate(
            ['id' => $listing->id()->toString()],
            [
                'seller_id' => $listing->sellerId()->toString(),
                'pet_id' => $listing->petId()->toString(),
                'price_amount' => $listing->price()->minorUnits,
                'currency' => $listing->price()->currency,
                'status' => $listing->status()->value,
            ],
        );
    }

    public function findById(Id $id): ?DomainListing
    {
        $model = EloquentListing::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findPublished(): array
    {
        return array_values(
            EloquentListing::query()
                ->where('status', ListingStatus::Published->value)
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findBySeller(Id $sellerId): array
    {
        return array_values(
            EloquentListing::query()
                ->where('seller_id', $sellerId->toString())
                ->orderByDesc('created_at')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findAllForReindex(?string $afterId, int $limit): array
    {
        return array_values(
            EloquentListing::query()
                ->when($afterId !== null, fn ($query) => $query->where('id', '>', $afterId))
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentListing $model): DomainListing
    {
        return DomainListing::reconstitute(
            id: Id::fromString($model->id),
            sellerId: Id::fromString($model->seller_id),
            petId: Id::fromString($model->pet_id),
            price: Money::fromMinorUnits((int) $model->price_amount, $model->currency),
            status: ListingStatus::from($model->status),
        );
    }
}
