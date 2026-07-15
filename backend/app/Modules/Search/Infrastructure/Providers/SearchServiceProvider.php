<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Providers;

use App\Modules\Marketplace\Domain\Events\ListingStatusChanged;
use App\Modules\Profile\Domain\Events\PetSaved;
use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Modules\Search\Infrastructure\Console\ConfigureSearchIndexesCommand;
use App\Modules\Search\Infrastructure\Console\ReindexSearchCommand;
use App\Modules\Search\Infrastructure\Listeners\ReindexListingOnListingStatusChanged;
use App\Modules\Search\Infrastructure\Listeners\ReindexPetOnPetSaved;
use App\Modules\Search\Infrastructure\Search\MeilisearchClientFactory;
use App\Modules\Search\Infrastructure\Search\MeilisearchListingIndex;
use App\Modules\Search\Infrastructure\Search\MeilisearchPetIndex;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;

final class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, fn () => MeilisearchClientFactory::make());

        $this->app->bind(PetSearchIndexInterface::class, MeilisearchPetIndex::class);
        $this->app->bind(ListingSearchIndexInterface::class, MeilisearchListingIndex::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ConfigureSearchIndexesCommand::class, ReindexSearchCommand::class]);
        }
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(PetSaved::class, ReindexPetOnPetSaved::class);
        $dispatcher->listen(ListingStatusChanged::class, ReindexListingOnListingStatusChanged::class);
    }
}
