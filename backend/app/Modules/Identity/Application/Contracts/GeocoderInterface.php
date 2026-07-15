<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Contracts;

interface GeocoderInterface
{
    /**
     * Возвращает null, если адрес не распознан или геокодер недоступен — адрес всё равно
     * сохраняется как текст, координаты/город в этом случае просто остаются null.
     */
    public function geocode(string $address): ?GeocodedAddress;
}
