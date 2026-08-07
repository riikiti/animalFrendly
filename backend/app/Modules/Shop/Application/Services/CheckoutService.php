<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Services;

use App\Modules\Shop\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Modules\Shop\Domain\Entities\ShopOrderItem;
use App\Modules\Shop\Domain\Enums\DeliveryMethod;
use App\Modules\Shop\Domain\Exceptions\EmptyCartException;
use App\Modules\Shop\Domain\Exceptions\ProductNotAvailableException;
use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ShopOrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\DB;

final class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CartRepositoryInterface $cartItems,
        private readonly ProductRepositoryInterface $products,
        private readonly ShopOrderRepositoryInterface $orders,
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    /**
     * Оформляет корзину в заказ и сразу инициирует оплату.
     *
     * @return array{order: ShopOrder, confirmation_url: string}
     */
    public function checkout(
        Id $buyerId,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
    ): array {
        $contents = $this->cart->contents($buyerId);

        if ($contents['items'] === []) {
            throw EmptyCartException::create();
        }

        return DB::transaction(function () use ($buyerId, $contents, $deliveryMethod, $deliveryAddress): array {
            $items = [];
            $sellerId = null;

            foreach ($contents['items'] as $line) {
                $product = $line['product'];

                // Остаток мог измениться, пока корзина лежала — списываем здесь,
                // внутри транзакции, и падаем, если товара уже не хватает.
                $product->takeFromStock($line['quantity']);
                $this->products->save($product);

                $items[] = new ShopOrderItem(
                    Id::generate(),
                    $product->id(),
                    $product->title(),
                    $product->price(),
                    $line['quantity'],
                );

                $sellerId ??= $product->sellerId();
            }

            $order = ShopOrder::create(
                $this->orders->nextIdentity(),
                $buyerId,
                $sellerId,
                $items,
                $deliveryMethod,
                $deliveryAddress,
            );

            $this->orders->save($order);
            $this->cartItems->clear($buyerId);

            $returnUrl = rtrim((string) config('yookassa.frontend_url'), '/')
                ."/shop/orders/{$order->id()->toString()}";
            $payment = $this->paymentGateway->initiate($order->id(), $order->amount(), $returnUrl);

            return ['order' => $order, 'confirmation_url' => $payment->confirmationUrl];
        });
    }

    /**
     * Возвращает товар на склад — при отмене неоплаченного заказа и при возврате денег.
     */
    public function returnItemsToStock(ShopOrder $order): void
    {
        foreach ($order->items() as $item) {
            $product = $this->products->findById($item->productId());

            if ($product === null) {
                continue;
            }

            $product->returnToStock($item->quantity());
            $this->products->save($product);
        }
    }

    /**
     * @throws ProductNotAvailableException
     */
    public function assertDeliveryAddress(DeliveryMethod $method, ?string $address): void
    {
        if ($method->needsAddress() && ($address === null || trim($address) === '')) {
            throw new \InvalidArgumentException('Укажите адрес доставки.');
        }
    }
}
