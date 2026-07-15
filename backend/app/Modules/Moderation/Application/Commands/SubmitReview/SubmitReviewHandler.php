<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\SubmitReview;

use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Modules\Moderation\Domain\Entities\Review;
use App\Modules\Moderation\Domain\Exceptions\ReviewAlreadySubmittedException;
use App\Modules\Moderation\Domain\Exceptions\ReviewNotEligibleException;
use App\Modules\Moderation\Domain\Repositories\ReviewRepositoryInterface;
use App\Modules\Shelter\Domain\Enums\AdoptionRequestStatus;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use InvalidArgumentException;

/**
 * Принимает ровно один из orderId/adoptionRequestId — прямая зависимость от Domain-репозиториев
 * Marketplace/Shelter (тот же приём, что Chat\...\MatchParticipantGuard/
 * AdoptionRequestParticipantGuard), см. docs/plan/03-architecture.md.
 */
final class SubmitReviewHandler
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviews,
        private readonly OrderRepositoryInterface $orders,
        private readonly AdoptionRequestRepositoryInterface $adoptionRequests,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly ShelterRepositoryInterface $shelters,
    ) {}

    public function handle(SubmitReviewCommand $command): Review
    {
        if (($command->orderId === null) === ($command->adoptionRequestId === null)) {
            throw new InvalidArgumentException('Ровно одно из orderId/adoptionRequestId должно быть задано.');
        }

        $authorId = Id::fromString($command->authorId);

        [$orderId, $adoptionRequestId, $targetUserId] = $command->orderId !== null
            ? $this->resolveForOrder($command->orderId, $authorId)
            : $this->resolveForAdoptionRequest((string) $command->adoptionRequestId, $authorId);

        if ($orderId !== null && $this->reviews->existsForAuthorAndOrder($authorId, $orderId)) {
            throw ReviewAlreadySubmittedException::create();
        }

        if ($adoptionRequestId !== null && $this->reviews->existsForAuthorAndAdoptionRequest($authorId, $adoptionRequestId)) {
            throw ReviewAlreadySubmittedException::create();
        }

        $review = Review::create(
            id: $this->reviews->nextIdentity(),
            orderId: $orderId,
            adoptionRequestId: $adoptionRequestId,
            authorId: $authorId,
            targetUserId: $targetUserId,
            rating: $command->rating,
            comment: $command->comment,
        );

        $this->reviews->save($review);

        return $review;
    }

    /**
     * @return array{0: Id, 1: null, 2: Id}
     */
    private function resolveForOrder(string $orderId, Id $authorId): array
    {
        $id = Id::fromString($orderId);
        $order = $this->orders->findById($id);

        if ($order === null) {
            throw OrderNotFoundException::forId($orderId);
        }

        if (! $order->isBuyer($authorId) || $order->status() !== OrderStatus::Completed) {
            throw ReviewNotEligibleException::create();
        }

        return [$id, null, $order->sellerId()];
    }

    /**
     * @return array{0: null, 1: Id, 2: Id}
     */
    private function resolveForAdoptionRequest(string $adoptionRequestId, Id $authorId): array
    {
        $id = Id::fromString($adoptionRequestId);
        $request = $this->adoptionRequests->findById($id);

        if ($request === null) {
            throw AdoptionRequestNotFoundException::forId($adoptionRequestId);
        }

        if (! $request->requesterUserId()->equals($authorId) || $request->status() !== AdoptionRequestStatus::Approved) {
            throw ReviewNotEligibleException::create();
        }

        $shelterAnimal = $this->shelterAnimals->findById($request->shelterAnimalId());
        $shelter = $shelterAnimal !== null ? $this->shelters->findById($shelterAnimal->shelterId()) : null;

        if ($shelter === null) {
            throw ReviewNotEligibleException::create();
        }

        return [null, $id, $shelter->ownerUserId()];
    }
}
