<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Marketplace\Application\Commands\OpenDispute\OpenDisputeCommand;
use App\Modules\Marketplace\Application\Commands\OpenDispute\OpenDisputeHandler;
use App\Modules\Marketplace\Application\Commands\ResolveDispute\ResolveDisputeCommand;
use App\Modules\Marketplace\Application\Commands\ResolveDispute\ResolveDisputeHandler;
use App\Modules\Marketplace\Domain\Exceptions\DisputeAlreadyOpenException;
use App\Modules\Marketplace\Domain\Exceptions\DisputeAlreadyResolvedException;
use App\Modules\Marketplace\Domain\Exceptions\DisputeNotFoundException;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Presentation\Http\Requests\OpenDisputeRequest;
use App\Modules\Marketplace\Presentation\Http\Requests\ResolveDisputeRequest;
use App\Modules\Marketplace\Presentation\Http\Resources\DisputeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DisputeController
{
    public function store(string $orderId, OpenDisputeRequest $request, OpenDisputeHandler $handler): JsonResponse
    {
        try {
            $dispute = $handler->handle(new OpenDisputeCommand(
                orderId: $orderId,
                actingUserId: $this->authenticatedUserId($request),
                reason: $request->string('reason')->toString(),
            ));
        } catch (OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotOrderPartyException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (DisputeAlreadyOpenException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new DisputeResource($dispute)], 201);
    }

    public function resolve(string $disputeId, ResolveDisputeRequest $request, ResolveDisputeHandler $handler): JsonResponse
    {
        // Gate::authorize() бросает AuthorizationException, которую Laravel сам превращает
        // в 403 через глобальный обработчик исключений — отдельный catch не нужен.
        Gate::authorize('resolve-marketplace-disputes');

        try {
            $dispute = $handler->handle(new ResolveDisputeCommand(
                disputeId: $disputeId,
                resolvedBy: $this->authenticatedUserId($request),
                resolution: $request->string('resolution')->toString(),
            ));
        } catch (DisputeNotFoundException|OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DisputeAlreadyResolvedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new DisputeResource($dispute)]);
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
