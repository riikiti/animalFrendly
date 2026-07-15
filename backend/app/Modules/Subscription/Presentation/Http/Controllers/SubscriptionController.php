<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Subscription\Application\Commands\CancelSubscription\CancelSubscriptionCommand;
use App\Modules\Subscription\Application\Commands\CancelSubscription\CancelSubscriptionHandler;
use App\Modules\Subscription\Application\Commands\SubscribeToPlan\SubscribeToPlanCommand;
use App\Modules\Subscription\Application\Commands\SubscribeToPlan\SubscribeToPlanHandler;
use App\Modules\Subscription\Application\Queries\GetMySubscription\GetMySubscriptionHandler;
use App\Modules\Subscription\Application\Queries\GetMySubscription\GetMySubscriptionQuery;
use App\Modules\Subscription\Application\Queries\ListPlans\ListPlansHandler;
use App\Modules\Subscription\Application\Queries\ListPlans\ListPlansQuery;
use App\Modules\Subscription\Domain\Exceptions\AlreadySubscribedException;
use App\Modules\Subscription\Domain\Exceptions\NoActiveSubscriptionException;
use App\Modules\Subscription\Domain\Exceptions\PlanNotFoundException;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Presentation\Http\Requests\SubscribeRequest;
use App\Modules\Subscription\Presentation\Http\Resources\SubscriptionPlanResource;
use App\Modules\Subscription\Presentation\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionController
{
    public function plans(ListPlansHandler $handler): JsonResponse
    {
        $plans = $handler->handle(new ListPlansQuery);

        return response()->json(['data' => SubscriptionPlanResource::collection($plans)]);
    }

    public function subscribe(SubscribeRequest $request, SubscribeToPlanHandler $handler): JsonResponse
    {
        $returnUrl = rtrim((string) config('yookassa.frontend_url'), '/').'/subscription/status';

        try {
            $result = $handler->handle(new SubscribeToPlanCommand(
                $request->string('plan_slug')->toString(),
                $this->authenticatedUserId($request),
                $returnUrl,
            ));
        } catch (PlanNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (AlreadySubscribedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'subscription' => new SubscriptionResource($result->subscription),
                'confirmation_url' => $result->confirmationUrl,
            ],
        ], 201);
    }

    public function me(
        Request $request,
        GetMySubscriptionHandler $handler,
        SubscriptionPlanRepositoryInterface $plans,
    ): JsonResponse {
        $subscription = $handler->handle(new GetMySubscriptionQuery($this->authenticatedUserId($request)));

        $plan = $subscription !== null
            ? $plans->findById($subscription->planId())
            : $plans->findBySlug('free');

        return response()->json([
            'data' => [
                'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
                'plan' => $plan ? new SubscriptionPlanResource($plan) : null,
            ],
        ]);
    }

    public function cancel(Request $request, CancelSubscriptionHandler $handler): JsonResponse
    {
        try {
            $subscription = $handler->handle(new CancelSubscriptionCommand($this->authenticatedUserId($request)));
        } catch (NoActiveSubscriptionException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => new SubscriptionResource($subscription)]);
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
