<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Moderation\Application\Commands\SubmitReview\SubmitReviewCommand;
use App\Modules\Moderation\Application\Commands\SubmitReview\SubmitReviewHandler;
use App\Modules\Moderation\Application\Queries\GetUserRatingSummary\GetUserRatingSummaryHandler;
use App\Modules\Moderation\Application\Queries\GetUserRatingSummary\GetUserRatingSummaryQuery;
use App\Modules\Moderation\Application\Queries\ListReviewsForUser\ListReviewsForUserHandler;
use App\Modules\Moderation\Application\Queries\ListReviewsForUser\ListReviewsForUserQuery;
use App\Modules\Moderation\Domain\Exceptions\ReviewAlreadySubmittedException;
use App\Modules\Moderation\Domain\Exceptions\ReviewNotEligibleException;
use App\Modules\Moderation\Presentation\Http\Requests\SubmitReviewRequest;
use App\Modules\Moderation\Presentation\Http\Resources\ReviewResource;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReviewController
{
    public function store(SubmitReviewRequest $request, SubmitReviewHandler $handler): JsonResponse
    {
        try {
            $review = $handler->handle(new SubmitReviewCommand(
                authorId: $this->authenticatedUserId($request),
                orderId: $request->string('order_id')->toString() ?: null,
                adoptionRequestId: $request->string('adoption_request_id')->toString() ?: null,
                rating: $request->integer('rating'),
                comment: $request->string('comment')->toString() ?: null,
            ));
        } catch (OrderNotFoundException|AdoptionRequestNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ReviewNotEligibleException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ReviewAlreadySubmittedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new ReviewResource($review)], 201);
    }

    public function index(string $userId, ListReviewsForUserHandler $handler): JsonResponse
    {
        $reviews = $handler->handle(new ListReviewsForUserQuery($userId));

        return response()->json(['data' => ReviewResource::collection($reviews)]);
    }

    public function rating(string $userId, GetUserRatingSummaryHandler $handler): JsonResponse
    {
        $summary = $handler->handle(new GetUserRatingSummaryQuery($userId));

        return response()->json(['data' => $summary]);
    }

    private function authenticatedUserId(Request $request): string
    {
        $user = $request->user();

        if (! $user instanceof IdentityUser) {
            abort(401);
        }

        return $user->id;
    }
}
