<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Console;

use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Modules\Search\Application\Indexing\BuildListingDocument;
use App\Modules\Search\Application\Indexing\BuildPetDocument;
use Illuminate\Console\Command;

/**
 * Полный реиндекс — страховка от рассинхрона (адрес владельца изменился задним числом, имя
 * породы обновилось и т.п.), запускается ночным расписанием (см. bootstrap/app.php) и вручную
 * после `search:configure-indexes`. Курсорная постраничная выборка по id (ULID монотонно
 * возрастает), обходит ВСЕ записи (не только подходящие под индекс) — билдер документа сам
 * решает, оставлять запись в индексе или нет.
 */
final class ReindexSearchCommand extends Command
{
    protected $signature = 'search:reindex {--index=all : pets|listings|all}';

    protected $description = 'Полностью пересобирает поисковые индексы Meilisearch из БД';

    private const int CHUNK_SIZE = 200;

    public function handle(
        PetRepositoryInterface $pets,
        ListingRepositoryInterface $listings,
        BuildPetDocument $buildPetDocument,
        BuildListingDocument $buildListingDocument,
        PetSearchIndexInterface $petIndex,
        ListingSearchIndexInterface $listingIndex,
    ): int {
        $target = (string) $this->option('index');

        if ($target === 'pets' || $target === 'all') {
            $petIndex->deleteAll();
            $count = $this->reindexPets($pets, $buildPetDocument, $petIndex);
            $this->info("Питомцы: обработано {$count}.");
        }

        if ($target === 'listings' || $target === 'all') {
            $listingIndex->deleteAll();
            $count = $this->reindexListings($listings, $buildListingDocument, $listingIndex);
            $this->info("Листинги: обработано {$count}.");
        }

        return self::SUCCESS;
    }

    private function reindexPets(
        PetRepositoryInterface $pets,
        BuildPetDocument $builder,
        PetSearchIndexInterface $index,
    ): int {
        $afterId = null;
        $total = 0;

        do {
            $batch = $pets->findAllForReindex($afterId, self::CHUNK_SIZE);

            foreach ($batch as $pet) {
                $document = $builder->build($pet);

                if ($document !== null) {
                    $index->putDocument($document);
                }

                $afterId = $pet->id()->toString();
                $total++;
            }
        } while (count($batch) === self::CHUNK_SIZE);

        return $total;
    }

    private function reindexListings(
        ListingRepositoryInterface $listings,
        BuildListingDocument $builder,
        ListingSearchIndexInterface $index,
    ): int {
        $afterId = null;
        $total = 0;

        do {
            $batch = $listings->findAllForReindex($afterId, self::CHUNK_SIZE);

            foreach ($batch as $listing) {
                $document = $builder->build($listing);

                if ($document !== null) {
                    $index->putDocument($document);
                }

                $afterId = $listing->id()->toString();
                $total++;
            }
        } while (count($batch) === self::CHUNK_SIZE);

        return $total;
    }
}
