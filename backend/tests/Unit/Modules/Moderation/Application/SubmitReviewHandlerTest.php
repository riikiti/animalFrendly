<?php

declare(strict_types=1);

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Modules\Moderation\Application\Commands\SubmitReview\SubmitReviewCommand;
use App\Modules\Moderation\Application\Commands\SubmitReview\SubmitReviewHandler;
use App\Modules\Moderation\Domain\Exceptions\ReviewAlreadySubmittedException;
use App\Modules\Moderation\Domain\Exceptions\ReviewNotEligibleException;
use App\Modules\Moderation\Domain\Repositories\ReviewRepositoryInterface;
use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makeCompletedOrder(Id $buyerId, Id $sellerId): Order
{
    $order = Order::create(Id::generate(), Id::generate(), $buyerId, $sellerId, Money::fromMinorUnits(10_000));
    $order->markPaid(Money::fromMinorUnits(500), 7);
    $order->autoConfirm();

    return $order;
}

function makeReviewMocks(): array
{
    return [
        Mockery::mock(ReviewRepositoryInterface::class),
        Mockery::mock(OrderRepositoryInterface::class),
        Mockery::mock(AdoptionRequestRepositoryInterface::class),
        Mockery::mock(ShelterAnimalRepositoryInterface::class),
        Mockery::mock(ShelterRepositoryInterface::class),
    ];
}

it('submits a review for a completed order by its buyer', function (): void {
    $buyerId = Id::generate();
    $sellerId = Id::generate();
    $order = makeCompletedOrder($buyerId, $sellerId);

    [$reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters] = makeReviewMocks();
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $reviews->shouldReceive('existsForAuthorAndOrder')->once()->andReturn(false);
    $reviews->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $reviews->shouldReceive('save')->once();

    $handler = new SubmitReviewHandler($reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters);
    $review = $handler->handle(new SubmitReviewCommand($buyerId->toString(), $order->id()->toString(), null, 5, 'Спасибо'));

    expect($review->targetUserId()->equals($sellerId))->toBeTrue();
});

it('rejects a review from someone who is not the buyer', function (): void {
    $order = makeCompletedOrder(Id::generate(), Id::generate());

    [$reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters] = makeReviewMocks();
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $reviews->shouldNotReceive('save');

    $handler = new SubmitReviewHandler($reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters);
    $handler->handle(new SubmitReviewCommand(Id::generate()->toString(), $order->id()->toString(), null, 5, null));
})->throws(ReviewNotEligibleException::class);

it('rejects a review for an order that is not completed yet', function (): void {
    $buyerId = Id::generate();
    $order = Order::create(Id::generate(), Id::generate(), $buyerId, Id::generate(), Money::fromMinorUnits(10_000));

    [$reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters] = makeReviewMocks();
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $reviews->shouldNotReceive('save');

    $handler = new SubmitReviewHandler($reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters);
    $handler->handle(new SubmitReviewCommand($buyerId->toString(), $order->id()->toString(), null, 5, null));
})->throws(ReviewNotEligibleException::class);

it('rejects a duplicate review for the same order', function (): void {
    $buyerId = Id::generate();
    $order = makeCompletedOrder($buyerId, Id::generate());

    [$reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters] = makeReviewMocks();
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $reviews->shouldReceive('existsForAuthorAndOrder')->once()->andReturn(true);
    $reviews->shouldNotReceive('save');

    $handler = new SubmitReviewHandler($reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters);
    $handler->handle(new SubmitReviewCommand($buyerId->toString(), $order->id()->toString(), null, 5, null));
})->throws(ReviewAlreadySubmittedException::class);

it('submits a review for an approved adoption request by its requester', function (): void {
    $requesterId = Id::generate();
    $shelterOwnerId = Id::generate();

    $shelterAnimalId = Id::generate();
    $shelterId = Id::generate();
    $request = AdoptionRequest::create(Id::generate(), $shelterAnimalId, $requesterId, null);
    $request->approve(Id::generate());

    $shelterAnimal = ShelterAnimal::publish($shelterAnimalId, $shelterId, Id::generate());
    $shelter = Shelter::register($shelterId, $shelterOwnerId, 'Приют', null, null);

    [$reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters] = makeReviewMocks();
    $adoptionRequests->shouldReceive('findById')->once()->andReturn($request);
    $shelterAnimals->shouldReceive('findById')->once()->andReturn($shelterAnimal);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);
    $reviews->shouldReceive('existsForAuthorAndAdoptionRequest')->once()->andReturn(false);
    $reviews->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $reviews->shouldReceive('save')->once();

    $handler = new SubmitReviewHandler($reviews, $orders, $adoptionRequests, $shelterAnimals, $shelters);
    $review = $handler->handle(new SubmitReviewCommand($requesterId->toString(), null, $request->id()->toString(), 5, null));

    expect($review->targetUserId()->equals($shelterOwnerId))->toBeTrue();
});
