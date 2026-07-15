<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Console;

use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use Illuminate\Console\Command;

/**
 * Идемпотентно выставляет filterable/sortable/searchable атрибуты обоих индексов Meilisearch —
 * запускается вручную один раз после поднятия Meilisearch (индекс создаётся неявно при первом
 * обращении, но атрибуты для фильтрации/сортировки/geo-поиска нужно настроить явно).
 */
final class ConfigureSearchIndexesCommand extends Command
{
    protected $signature = 'search:configure-indexes';

    protected $description = 'Настраивает filterable/sortable/searchable атрибуты индексов Meilisearch';

    public function handle(PetSearchIndexInterface $pets, ListingSearchIndexInterface $listings): int
    {
        $pets->configureIndex();
        $listings->configureIndex();

        $this->info('Индексы pets и listings настроены.');

        return self::SUCCESS;
    }
}
