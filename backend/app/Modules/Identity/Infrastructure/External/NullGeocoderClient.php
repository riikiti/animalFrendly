<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\External;

use App\Modules\Identity\Application\Contracts\GeocodedAddress;
use App\Modules\Identity\Application\Contracts\GeocoderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Фоллбэк для локальной разработки, когда YANDEX_GEOCODER_API_KEY не заполнен — тот же
 * принцип, что NullFcmClient/NullYookassaClient. Байндится вместо YandexGeocoderClient в
 * IdentityServiceProvider::register().
 */
final class NullGeocoderClient implements GeocoderInterface
{
    public function geocode(string $address): ?GeocodedAddress
    {
        Log::info('geocoder.geocode', ['address' => $address]);

        return null;
    }
}
