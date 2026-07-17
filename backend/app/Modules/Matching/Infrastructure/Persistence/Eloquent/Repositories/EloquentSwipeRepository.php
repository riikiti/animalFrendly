<?php

declare(strict_types=1);

namespace App\Modules\Matching\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Matching\Domain\Entities\Swipe as DomainSwipe;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Matching\Infrastructure\Persistence\Eloquent\Models\Swipe as EloquentSwipe;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentSwipeRepository implements SwipeRepositoryInterface
{
    public function record(DomainSwipe $swipe): void
    {
        EloquentSwipe::query()->create([
            'swiper_pet_id' => $swipe->swiperPetId->toString(),
            'target_pet_id' => $swipe->targetPetId->toString(),
            'action' => $swipe->action->value,
            'created_at' => $swipe->createdAt,
        ]);
    }

    public function hasSwiped(Id $swiperPetId, Id $targetPetId): bool
    {
        return EloquentSwipe::query()
            ->where('swiper_pet_id', $swiperPetId->toString())
            ->where('target_pet_id', $targetPetId->toString())
            ->exists();
    }

    public function hasLiked(Id $fromPetId, Id $toPetId): bool
    {
        return EloquentSwipe::query()
            ->where('swiper_pet_id', $fromPetId->toString())
            ->where('target_pet_id', $toPetId->toString())
            ->whereIn('action', ['like', 'super_like'])
            ->exists();
    }

    public function swipedTargetIds(Id $swiperPetId): array
    {
        return array_values(
            EloquentSwipe::query()
                ->where('swiper_pet_id', $swiperPetId->toString())
                ->pluck('target_pet_id')
                ->map(static fn (string $id): Id => Id::fromString($id))
                ->all(),
        );
    }

    public function pendingIncomingLikes(Id $targetPetId): array
    {
        return array_values(
            EloquentSwipe::query()
                ->where('target_pet_id', $targetPetId->toString())
                ->whereIn('action', ['like', 'super_like'])
                ->whereNotIn('swiper_pet_id', function ($query) use ($targetPetId): void {
                    $query->select('target_pet_id')
                        ->from('swipes')
                        ->where('swiper_pet_id', $targetPetId->toString());
                })
                ->orderByDesc('created_at')
                ->pluck('swiper_pet_id')
                ->map(static fn (string $id): Id => Id::fromString($id))
                ->all(),
        );
    }

    public function pendingOutgoingLikes(Id $swiperPetId): array
    {
        return array_values(
            EloquentSwipe::query()
                ->where('swiper_pet_id', $swiperPetId->toString())
                ->whereIn('action', ['like', 'super_like'])
                ->whereNotIn('target_pet_id', function ($query) use ($swiperPetId): void {
                    $query->select('swiper_pet_id')
                        ->from('swipes')
                        ->where('target_pet_id', $swiperPetId->toString());
                })
                ->orderByDesc('created_at')
                ->pluck('target_pet_id')
                ->map(static fn (string $id): Id => Id::fromString($id))
                ->all(),
        );
    }
}
