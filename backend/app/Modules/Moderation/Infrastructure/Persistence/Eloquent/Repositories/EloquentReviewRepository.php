<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Moderation\Domain\Entities\Review as DomainReview;
use App\Modules\Moderation\Domain\Repositories\ReviewRepositoryInterface;
use App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Models\Review as EloquentReview;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentReviewRepository implements ReviewRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainReview $review): void
    {
        EloquentReview::query()->updateOrCreate(
            ['id' => $review->id()->toString()],
            [
                'order_id' => $review->orderId()?->toString(),
                'adoption_request_id' => $review->adoptionRequestId()?->toString(),
                'author_id' => $review->authorId()->toString(),
                'target_user_id' => $review->targetUserId()->toString(),
                'rating' => $review->rating(),
                'comment' => $review->comment(),
                'created_at' => $review->createdAt(),
            ],
        );
    }

    public function existsForAuthorAndOrder(Id $authorId, Id $orderId): bool
    {
        return EloquentReview::query()
            ->where('author_id', $authorId->toString())
            ->where('order_id', $orderId->toString())
            ->exists();
    }

    public function existsForAuthorAndAdoptionRequest(Id $authorId, Id $adoptionRequestId): bool
    {
        return EloquentReview::query()
            ->where('author_id', $authorId->toString())
            ->where('adoption_request_id', $adoptionRequestId->toString())
            ->exists();
    }

    public function findForTargetUser(Id $userId): array
    {
        return array_values(
            EloquentReview::query()
                ->where('target_user_id', $userId->toString())
                ->orderByDesc('id')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function ratingSummaryForUser(Id $userId): array
    {
        $query = EloquentReview::query()->where('target_user_id', $userId->toString());
        $count = $query->count();

        return [
            'average' => $count > 0 ? round((float) $query->avg('rating'), 2) : 0.0,
            'count' => $count,
        ];
    }

    private function toDomain(EloquentReview $model): DomainReview
    {
        return DomainReview::reconstitute(
            id: Id::fromString($model->id),
            orderId: $model->order_id !== null ? Id::fromString($model->order_id) : null,
            adoptionRequestId: $model->adoption_request_id !== null ? Id::fromString($model->adoption_request_id) : null,
            authorId: Id::fromString($model->author_id),
            targetUserId: Id::fromString($model->target_user_id),
            rating: $model->rating,
            comment: $model->comment,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
