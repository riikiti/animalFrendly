<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Providers;

use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Chat\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Chat\Infrastructure\Listeners\CreateConversationOnPetsMatched;
use App\Modules\Chat\Infrastructure\Persistence\Eloquent\Repositories\EloquentConversationRepository;
use App\Modules\Chat\Infrastructure\Persistence\Eloquent\Repositories\EloquentMessageRepository;
use App\Modules\Matching\Domain\Events\PetsMatched;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConversationRepositoryInterface::class, EloquentConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, EloquentMessageRepository::class);
    }

    public function boot(): void
    {
        $this->app->make(Dispatcher::class)->listen(PetsMatched::class, CreateConversationOnPetsMatched::class);
    }
}
