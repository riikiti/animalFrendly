<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Controllers;

use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Marketplace\Application\Commands\CancelOrder\CancelOrderCommand;
use App\Modules\Marketplace\Application\Commands\CancelOrder\CancelOrderHandler;
use App\Modules\Marketplace\Application\Commands\ConfirmOrder\ConfirmOrderCommand;
use App\Modules\Marketplace\Application\Commands\ConfirmOrder\ConfirmOrderHandler;
use App\Modules\Marketplace\Application\Commands\PurchaseListing\PurchaseListingCommand;
use App\Modules\Marketplace\Application\Commands\PurchaseListing\PurchaseListingHandler;
use App\Modules\Marketplace\Application\Queries\GetOrder\GetOrderHandler;
use App\Modules\Marketplace\Application\Queries\GetOrder\GetOrderQuery;
use App\Modules\Marketplace\Application\Queries\ListMyOrders\ListMyOrdersHandler;
use App\Modules\Marketplace\Application\Queries\ListMyOrders\ListMyOrdersQuery;
use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Exceptions\CannotPurchaseOwnListingException;
use App\Modules\Marketplace\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotAvailableException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotFoundException;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Exceptions\OrderAlreadyConfirmedException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Presentation\Http\Resources\OrderResource;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController
{
    public function purchase(string $listingId, Request $request, PurchaseListingHandler $handler): JsonResponse
    {
        try {
            $result = $handler->handle(new PurchaseListingCommand($listingId, $this->authenticatedUserId($request)));
        } catch (ListingNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ListingNotAvailableException|CannotPurchaseOwnListingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'order' => new OrderResource($result->order),
                'confirmation_url' => $result->confirmationUrl,
            ],
        ], 201);
    }

    public function index(Request $request, ListMyOrdersHandler $handler): JsonResponse
    {
        $role = $request->string('role')->toString() === 'seller' ? 'seller' : 'buyer';

        $orders = $handler->handle(new ListMyOrdersQuery($this->authenticatedUserId($request), $role));

        return response()->json(['data' => OrderResource::collection($orders)]);
    }

    public function show(string $orderId, Request $request, GetOrderHandler $handler, UserRepositoryInterface $users): JsonResponse
    {
        try {
            $order = $handler->handle(new GetOrderQuery($orderId, $this->authenticatedUserId($request)));
        } catch (OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotOrderPartyException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        [$counterpartAddress, $counterpartLocation] = $this->resolveCounterpartLocation(
            $order,
            Id::fromString($this->authenticatedUserId($request)),
            $users,
        );

        return response()->json(['data' => new OrderResource([
            'order' => $order,
            'counterpart_address' => $counterpartAddress,
            'counterpart_location' => $counterpartLocation,
        ])]);
    }

    /**
     * Точный адрес контрагента виден только участнику сделки и только после оплаты — до этого
     * момента показывать домашний адрес незнакомому человеку небезопасно, см. Search-фазу.
     *
     * @return array{0: ?string, 1: ?array{lat: float, lng: float}}
     */
    private function resolveCounterpartLocation(Order $order, Id $actingUserId, UserRepositoryInterface $users): array
    {
        if (in_array($order->status(), [OrderStatus::PendingPayment, OrderStatus::Cancelled], true)) {
            return [null, null];
        }

        $counterpartId = $order->isBuyer($actingUserId) ? $order->sellerId() : $order->buyerId();
        $counterpart = $users->findById($counterpartId);

        if ($counterpart === null || $counterpart->address() === null) {
            return [null, null];
        }

        $location = $counterpart->hasCoordinates()
            ? ['lat' => (float) $counterpart->latitude(), 'lng' => (float) $counterpart->longitude()]
            : null;

        return [$counterpart->address(), $location];
    }

    public function confirm(string $orderId, Request $request, ConfirmOrderHandler $handler): JsonResponse
    {
        try {
            $order = $handler->handle(new ConfirmOrderCommand($orderId, $this->authenticatedUserId($request)));
        } catch (OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotOrderPartyException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (InvalidOrderStatusTransitionException|OrderAlreadyConfirmedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function cancel(string $orderId, Request $request, CancelOrderHandler $handler): JsonResponse
    {
        try {
            $order = $handler->handle(new CancelOrderCommand($orderId, $this->authenticatedUserId($request)));
        } catch (OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotOrderPartyException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (InvalidOrderStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new OrderResource($order)]);
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
