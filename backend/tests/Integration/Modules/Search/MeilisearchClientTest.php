<?php

declare(strict_types=1);

use App\Modules\Search\Application\Queries\SearchPets\SearchPetsQuery;
use App\Modules\Search\Infrastructure\Search\MeilisearchClientFactory;
use App\Modules\Search\Infrastructure\Search\MeilisearchPetIndex;
use App\Shared\Domain\ValueObjects\Id;

/**
 * meilisearch-php использует свой собственный PSR-18 HTTP-клиент (не Laravel Http-фасад),
 * поэтому Http::fake() его не перехватывает — единственный способ проверить реальный wire-
 * format здесь это ударить по локальному Meilisearch-контейнеру (уже поднят docker-compose'ом
 * для разработки). Пропускаем тест, если он недоступен — не ломает прогон на других машинах/CI.
 */
beforeEach(function (): void {
    config(['meilisearch.pets_index' => 'test_pets_integration']);

    try {
        MeilisearchClientFactory::make()->health();
    } catch (Throwable) {
        test()->markTestSkipped('Meilisearch недоступен по MEILISEARCH_HOST — пропускаем интеграционный тест.');
    }
});

afterEach(function (): void {
    try {
        MeilisearchClientFactory::make()->index('test_pets_integration')->deleteAllDocuments();
    } catch (Throwable) {
        // индекс мог не успеть создаться — не критично для очистки
    }
});

it('configures, indexes, finds by filter and deletes a document on the real Meilisearch instance', function (): void {
    $index = new MeilisearchPetIndex(MeilisearchClientFactory::make());
    $index->configureIndex();

    $petId = Id::generate()->toString();
    $index->putDocument([
        'id' => $petId,
        'name' => 'Интеграционный Рекс',
        'species_id' => 1,
        'species_name' => 'Собака',
        'breed_id' => null,
        'breed_name' => null,
        'sex' => 'male',
        'purpose' => 'social',
        'city' => 'Тестоград',
        'is_vaccinated' => true,
        'is_boosted' => false,
        'photo_url' => null,
    ]);

    $query = new SearchPetsQuery(actingUserId: Id::generate()->toString(), city: 'Тестоград');
    $found = false;

    // Meilisearch применяет настройки индекса и индексирует документы асинхронно — короткий
    // пуллинг вместо ожидания конкретных задач; пока sortable-атрибуты ещё не применились,
    // search() может кидать ApiException, это тоже повод повторить попытку.
    for ($attempt = 0; $attempt < 30; $attempt++) {
        try {
            $page = $index->search($query, null, null);

            if (count($page->items) === 1 && $page->items[0]->id === $petId) {
                $found = true;

                break;
            }
        } catch (Throwable) {
            // индекс/настройки ещё не готовы — повторим
        }

        usleep(200_000);
    }

    expect($found)->toBeTrue();

    $index->deleteDocument($petId);
});
