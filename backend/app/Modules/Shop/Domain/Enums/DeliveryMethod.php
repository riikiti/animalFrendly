<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Enums;

enum DeliveryMethod: string
{
    case Pickup = 'pickup';
    case Pvz = 'pvz';
    case Courier = 'courier';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Самовывоз',
            self::Pvz => 'СДЭК до пункта выдачи',
            self::Courier => 'Курьер по городу',
        };
    }

    /**
     * Стоимость доставки в копейках. Тарифы фиксированные — расчёта по весу и адресу пока нет.
     */
    public function priceMinorUnits(): int
    {
        return match ($this) {
            self::Pickup => 0,
            self::Pvz => 20000,
            self::Courier => 35000,
        };
    }

    public function needsAddress(): bool
    {
        return $this !== self::Pickup;
    }
}
