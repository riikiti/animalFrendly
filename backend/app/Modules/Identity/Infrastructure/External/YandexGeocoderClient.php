<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\External;

use App\Modules\Identity\Application\Contracts\GeocodedAddress;
use App\Modules\Identity\Application\Contracts\GeocoderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Yandex Geocoder API (https://yandex.ru/dev/geocode/) — координаты и город по свободному
 * тексту адреса. Ошибки/недоступность сервиса не выбрасываются наружу: адрес — не критичный
 * путь, при сбое просто нет координат, пользователь может пересохранить адрес позже.
 */
final class YandexGeocoderClient implements GeocoderInterface
{
    public function geocode(string $address): ?GeocodedAddress
    {
        try {
            $response = Http::get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey' => config('geocoder.yandex_api_key'),
                'geocode' => $address,
                'format' => 'json',
                'results' => 1,
            ]);

            if ($response->failed()) {
                Log::warning('geocoder.request_failed', ['address' => $address, 'status' => $response->status()]);

                return null;
            }

            $geoObject = $response->json('response.GeoObjectCollection.featureMember.0.GeoObject');

            if (! is_array($geoObject)) {
                return null;
            }

            $pos = $geoObject['Point']['pos'] ?? null;

            if (! is_string($pos)) {
                return null;
            }

            [$longitude, $latitude] = array_map('floatval', explode(' ', $pos, 2));

            return new GeocodedAddress(
                formattedAddress: (string) ($geoObject['metaDataProperty']['GeocoderMetaData']['text'] ?? $address),
                city: $this->extractCity($geoObject),
                latitude: $latitude,
                longitude: $longitude,
            );
        } catch (Throwable $e) {
            Log::warning('geocoder.request_error', ['address' => $address, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $geoObject
     */
    private function extractCity(array $geoObject): ?string
    {
        $components = $geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'] ?? [];

        if (! is_array($components)) {
            return null;
        }

        foreach ($components as $component) {
            if (is_array($component) && ($component['kind'] ?? null) === 'locality') {
                return is_string($component['name'] ?? null) ? $component['name'] : null;
            }
        }

        return null;
    }
}
