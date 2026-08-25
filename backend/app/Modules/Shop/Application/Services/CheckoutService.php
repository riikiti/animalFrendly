<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Services;

use App\Modules\Shop\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Modules\Shop\Domain\Entities\ShopOrderItem;
use App\Modules\Shop\Domain\Enums\DeliveryMethod;
use App\Modules\Shop\Domain\Exceptions\EmptyCartException;
use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ShopOrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
     * Оформляет корзину и инициирует оплату.
     *
     * Товары разных продавцов разъезжаются на отдельные заказы: комиссия, эскроу и
     * выплата считаются на конкретного получателя денег. Платёж при этом один — он
     * покрывает все заказы одного оформления и связан с ними общим checkout_id.
     * Доставка тоже считается по заказу: посылки едут от разных продавцов.
     *
     * @return array{orders: array<int, ShopOrder>, checkout_id: Id, amount: Money, confirmation_url: string}
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
            $checkoutId = $this->orders->startCheckout($buyerId);
            $orders = [];
            $total = Money::zero();

            foreach ($contents['groups'] as $group) {
                $items = [];

                foreach ($group['items'] as $line) {
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
                }

                $order = ShopOrder::create(
                    $this->orders->nextIdentity(),
                    $checkoutId,
                    $buyerId,
                    Id::fromString($group['seller_id']),
                    $items,
                    $deliveryMethod,
                    $deliveryAddress,
                );

                $this->orders->save($order);
                $orders[] = $order;
                $total = $total->add($order->amount());
            }

            $this->orders->setCheckoutAmount($checkoutId, $total);
            $this->cartItems->clear($buyerId);

            $returnUrl = rtrim((string) config('yookassa.frontend_url'), '/').'/shop/orders';
            $payment = $this->paymentGateway->initiate($checkoutId, $total, $returnUrl);

            return [
                'orders' => $orders,
                'checkout_id' => $checkoutId,
                'amount' => $total,
                'confirmation_url' => $payment->confirmationUrl,
            ];
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

    public function assertDeliveryAddress(DeliveryMethod $method, ?string $address): void
    {
        if ($method->needsAddress() && ($address === null || trim($address) === '')) {
            throw new InvalidArgumentException('Укажите адрес доставки.');
        }
    }
}
