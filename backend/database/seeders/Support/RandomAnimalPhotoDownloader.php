<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Скачивает реальные фото животных/людей с открытых API — только для DemoSeeder (dev-БД
 * для ручного браузинга), никогда не используется в Pest-фабриках (там нужна скорость и
 * отсутствие сетевых зависимостей — см. docs/plan/00-overview.md, договорённость с
 * пользователем). Сетевые сбои не фатальны — сидер просто пропускает фото для конкретного
 * питомца/приюта/пользователя, см. вызывающий код в DemoSeeder.
 */
final class RandomAnimalPhotoDownloader
{
    /** @var array<string, list<string>> Пул URL по ключу (dog/cat/face) — заполняется одним batched-запросом. */
    private array $pools = [];

    public function forSpecies(string $speciesSlug): ?UploadedFile
    {
        $url = match ($speciesSlug) {
            'dog' => $this->nextFromPool('dog', $this->fetchDogPool(...)),
            'cat' => $this->nextFromPool('cat', $this->fetchCatPool(...)),
            'bird' => $this->loremFlickrUrl('bird'),
            default => $this->loremFlickrUrl(fake()->randomElement(['pet', 'animal'])),
        };

        return $url !== null ? $this->download($url) : null;
    }

    public function humanFace(): ?UploadedFile
    {
        $url = $this->nextFromPool('face', $this->fetchFacePool(...));

        return $url !== null ? $this->download($url) : null;
    }

    /**
     * @param  callable(): list<string>  $fetch
     */
    private function nextFromPool(string $key, callable $fetch): ?string
    {
        if (! isset($this->pools[$key]) || $this->pools[$key] === []) {
            $this->pools[$key] = $fetch();
        }

        return array_pop($this->pools[$key]);
    }

    /**
     * @return list<string>
     */
    private function fetchDogPool(): array
    {
        try {
            $urls = $this->http()->get('https://dog.ceo/api/breeds/image/random/40')->json('message');

            return is_array($urls) ? array_values(array_filter($urls, 'is_string')) : [];
        } catch (Throwable $e) {
            Log::warning('demo_seeder.dog_pool_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<string>
     */
    private function fetchCatPool(): array
    {
        try {
            $response = $this->http()->get('https://api.thecatapi.com/v1/images/search', ['limit' => 40])->json();

            if (! is_array($response)) {
                return [];
            }

            return collect($response)
                ->pluck('url')
                ->filter(fn (mixed $url): bool => is_string($url) && preg_match('/\.(jpe?g|png|webp)$/i', $url) === 1)
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('demo_seeder.cat_pool_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<string>
     */
    private function fetchFacePool(): array
    {
        try {
            $response = $this->http()->get('https://randomuser.me/api/', ['results' => 40, 'inc' => 'picture'])->json('results');

            if (! is_array($response)) {
                return [];
            }

            return collect($response)
                ->pluck('picture.large')
                ->filter(fn (mixed $url): bool => is_string($url))
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('demo_seeder.face_pool_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * loremflickr отдаёт реальное фото с Flickr по ключевому слову без ключа/лимитов —
     * каждый вызов уже даёт свежий кадр (через ?lock=), пулинг тут не нужен.
     */
    private function loremFlickrUrl(string $keyword): string
    {
        return "https://loremflickr.com/640/480/{$keyword}?lock=".random_int(1, 1_000_000);
    }

    /**
     * withoutVerifying() — на этой dev-машине (Windows) у PHP не настроен curl.cainfo
     * (нет системного CA-бандла), из-за чего любой HTTPS-запрос из PHP падает с "unable to
     * get local issuer certificate", хотя те же URL прекрасно открываются в браузере/curl.
     * Приемлемо только здесь: сидер обращается к публичным демо-API за случайными фото,
     * не передаёт и не получает ничего чувствительного — см. класс-докблок.
     */
    private function http(): PendingRequest
    {
        return Http::timeout(10)->withoutVerifying();
    }

    private function download(string $url): ?UploadedFile
    {
        try {
            $response = $this->http()->get($url);

            if ($response->failed()) {
                Log::warning('demo_seeder.photo_download_failed', ['url' => $url, 'status' => $response->status()]);

                return null;
            }

            $extension = match (true) {
                str_contains((string) $response->header('Content-Type'), 'png') => 'png',
                str_contains((string) $response->header('Content-Type'), 'webp') => 'webp',
                default => 'jpg',
            };

            $tmpPath = tempnam(sys_get_temp_dir(), 'demo_photo_');

            if ($tmpPath === false) {
                return null;
            }

            $finalPath = $tmpPath.'.'.$extension;
            rename($tmpPath, $finalPath);
            file_put_contents($finalPath, $response->body());

            return new UploadedFile($finalPath, 'photo.'.$extension, $response->header('Content-Type'), null, true);
        } catch (Throwable $e) {
            Log::warning('demo_seeder.photo_download_error', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
