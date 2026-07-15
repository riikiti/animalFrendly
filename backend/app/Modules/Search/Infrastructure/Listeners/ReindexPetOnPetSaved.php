<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Listeners;

use App\Modules\Profile\Domain\Events\PetSaved;
use App\Modules\Search\Infrastructure\Jobs\IndexPetJob;

final class ReindexPetOnPetSaved
{
    public function handle(PetSaved $event): void
    {
        IndexPetJob::dispatch($event->petId->toString());
    }
}
