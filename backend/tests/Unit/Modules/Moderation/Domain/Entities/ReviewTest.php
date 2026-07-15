<?php

declare(strict_types=1);

use App\Modules\Moderation\Domain\Entities\Review;
use App\Shared\Domain\ValueObjects\Id;

it('creates a review for an order', function (): void {
    $orderId = Id::generate();
    $review = Review::create(
        id: Id::generate(),
        orderId: $orderId,
        adoptionRequestId: null,
        authorId: Id::generate(),
        targetUserId: Id::generate(),
        rating: 5,
        comment: 'Отлично',
    );

    expect($review->orderId()->equals($orderId))->toBeTrue()
        ->and($review->adoptionRequestId())->toBeNull()
        ->and($review->rating())->toBe(5);
});

it('creates a review for an adoption request', function (): void {
    $adoptionRequestId = Id::generate();
    $review = Review::create(
        id: Id::generate(),
        orderId: null,
        adoptionRequestId: $adoptionRequestId,
        authorId: Id::generate(),
        targetUserId: Id::generate(),
        rating: 4,
        comment: null,
    );

    expect($review->adoptionRequestId()->equals($adoptionRequestId))->toBeTrue()
        ->and($review->orderId())->toBeNull();
});

it('rejects a review with both order and adoption request set', function (): void {
    Review::create(
        id: Id::generate(),
        orderId: Id::generate(),
        adoptionRequestId: Id::generate(),
        authorId: Id::generate(),
        targetUserId: Id::generate(),
        rating: 5,
        comment: null,
    );
})->throws(InvalidArgumentException::class);

it('rejects a review with neither order nor adoption request set', function (): void {
    Review::create(
        id: Id::generate(),
        orderId: null,
        adoptionRequestId: null,
        authorId: Id::generate(),
        targetUserId: Id::generate(),
        rating: 5,
        comment: null,
    );
})->throws(InvalidArgumentException::class);

it('rejects a rating outside 1..5', function (int $rating): void {
    Review::create(
        id: Id::generate(),
        orderId: Id::generate(),
        adoptionRequestId: null,
        authorId: Id::generate(),
        targetUserId: Id::generate(),
        rating: $rating,
        comment: null,
    );
})->with([0, 6, -1])->throws(InvalidArgumentException::class);
