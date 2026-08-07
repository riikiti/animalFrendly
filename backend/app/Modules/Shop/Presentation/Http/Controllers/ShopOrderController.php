<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Controllers;

use App\Modules\Shop\Application\Services\CheckoutService;
use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Modules\Shop\Domain\Enums\DeliveryMethod;
use App\Modules\Shop\Domain\Exceptions\EmptyCartException;
use App\Modules\Shop\Domain\Exceptions\ProductNotAvailableException;
use App\Modules\Shop\Domain\Repositories\ShopOrderRepositoryInterface;
use App\Modules\Shop\Presentation\Http\Requests\CheckoutRequest;
use App\Modules\Shop\Presentation\Http\Resources\ShopOrderResource;
use App\Shared\Domain\ValueObjects\Id;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class ShopOrderController
{
    public function __construct(
        private readonly ShopOrderRepositoryInterface $orders,
        private readonly CheckoutService $checkout,
    ) {}

    public function deliveryOptions(): JsonResponse
    {
        return response()->json([
            'data' => array_map(static fn (DeliveryMethod $method): array => [
                'value' => $method->value,
                'label' => $method->label(),
                'price_amount' => $method->priceMinorUnits(),
                'needs_address' => $method->needsAddress(),
            ], DeliveryMethod::cases()),
        ]);
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $method = DeliveryMethod::from($request->string('delivery_method')->toString());
        $address = $request->string('delivery_address')->toString() ?: null;

        try {
            $this->checkout->assertDeliveryAddress($method, $address);

            $result = $this->checkout->checkout(
                Id::fromString($this->userId($request)),
                $method,
                $address,
            );
        } catch (EmptyCartException|ProductNotAvailableException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => new ShopOrderResource($result['order']),
            'confirmation_url' => $result['confirmation_url'],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $role = $request->string('role')->toString() === 'seller' ? 'seller' : 'buyer';

        return response()->json([
            'data' => ShopOrderResource::collection(
                $this->orders->listFor(Id::fromString($this->userId($request)), $role),
            ),
        ]);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        return response()->json(['data' => new ShopOrderResource($this->participantOrder($id, $request))]);
    }

    public function ship(string $id, Request $request): JsonResponse
    {
        $order = $this->participantOrder($id, $request);

        if (! $order->isSeller(Id::fromString($this->userId($request)))) {
            abort(403, 'Отправить заказ может только продавец.');
        }

        return $this->transition($order, static function (ShopOrder $o): void {
            $o->markShipped();
        });
    }

    public function confirm(string $id, Request $request): JsonResponse
    {
        $order = $this->participantOrder($id, $request);
        $userId = Id::fromString($this->userId($request));

        return $this->transition($order, static function (ShopOrder $o) use ($userId): void {
            if ($o->isBuyer($userId)) {
                $o->confirmByBuyer();

                return;
            }

            $o->confirmBySeller();
        });
    }

    public function dispute(string $id, Request $request): JsonResponse
    {
        $order = $this->participantOrder($id, $request);

        return $this->transition($order, static function (ShopOrder $o): void {
            $o->openDispute();
        });
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        $order = $this->participantOrder($id, $request);

        if (! $order->isBuyer(Id::fromString($this->userId($request)))) {
            abort(403, 'Отменить заказ может только покупатель.');
        }

        try {
            $order->cancel();
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Неоплаченный заказ отменён — товар возвращается на витрину.
        $this->checkout->returnItemsToStock($order);
        $this->orders->save($order);

        return response()->json(['data' => new ShopOrderResource($order)]);
    }

    private function transition(ShopOrder $order, callable $action): JsonResponse
    {
        try {
            $action($order);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->orders->save($order);

        return response()->json(['data' => new ShopOrderResource($order)]);
    }

    private function participantOrder(string $id, Request $request): ShopOrder
    {
        $order = $this->orders->findById(Id::fromString($id));

        if ($order === null) {
            abort(404, 'Заказ не найден.');
        }

        $userId = Id::fromString($this->userId($request));

        if (! $order->isBuyer($userId) && ! $order->isSeller($userId)) {
            abort(403, 'Это чужой заказ.');
        }

        return $order;
    }

    private function userId(Request $request): string
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return (string) $user->getAuthIdentifier();
    }
}
