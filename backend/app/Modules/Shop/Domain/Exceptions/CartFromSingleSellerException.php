<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Exceptions;

use DomainException;

/**
 * Заказ уходит одному продавцу — на него считаются комиссия, эскроу и выплата.
 * Поэтому корзина тоже держит товары одного продавца.
 */
final class CartFromSingleSellerException extends DomainException
{
    public static function create(): self
    {
        return new self('В корзине уже есть товары другого продавца. Оформите их или очистите корзину.');
    }
}
