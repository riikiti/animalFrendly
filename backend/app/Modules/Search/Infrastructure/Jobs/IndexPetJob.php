<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Jobs;

use App\Modules\Search\Application\Indexing\IndexPetService;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Best-effort: сбой индексации не должен ронять основной поток (создание/сохранение питомца
 * уже завершилось синхронно) — исключение ловится и логируется, не пробрасывается дальше.
 */
final class IndexPetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $petId) {}

    public function handle(IndexPetService $service): void
    {
        try {
            $service->index(Id::fromString($this->petId));
        } catch (Throwable $e) {
            Log::warning('search.index_pet.failed', ['pet_id' => $this->petId, 'error' => $e->getMessage()]);
        }
    }
}
