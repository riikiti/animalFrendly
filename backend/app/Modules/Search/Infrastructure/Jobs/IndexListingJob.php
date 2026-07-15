<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Jobs;

use App\Modules\Search\Application\Indexing\IndexListingService;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class IndexListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $listingId) {}

    public function handle(IndexListingService $service): void
    {
        try {
            $service->index(Id::fromString($this->listingId));
        } catch (Throwable $e) {
            Log::warning('search.index_listing.failed', ['listing_id' => $this->listingId, 'error' => $e->getMessage()]);
        }
    }
}
