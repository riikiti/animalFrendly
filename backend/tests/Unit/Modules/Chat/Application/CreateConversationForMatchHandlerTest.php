<?php

declare(strict_types=1);

use App\Modules\Chat\Application\Commands\CreateConversationForMatch\CreateConversationForMatchCommand;
use App\Modules\Chat\Application\Commands\CreateConversationForMatch\CreateConversationForMatchHandler;
use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('creates a conversation for a match that has none yet', function (): void {
    $matchId = Id::generate();

    $conversations = Mockery::mock(ConversationRepositoryInterface::class);
    $conversations->shouldReceive('findByMatchId')->once()->andReturn(null);
    $conversations->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $conversations->shouldReceive('save')->once();

    $handler = new CreateConversationForMatchHandler($conversations);
    $conversation = $handler->handle(new CreateConversationForMatchCommand($matchId->toString()));

    expect($conversation->matchId()->equals($matchId))->toBeTrue();
});

it('is idempotent — reuses an existing conversation for the match', function (): void {
    $matchId = Id::generate();
    $existing = Conversation::createForMatch(Id::generate(), $matchId);

    $conversations = Mockery::mock(ConversationRepositoryInterface::class);
    $conversations->shouldReceive('findByMatchId')->once()->andReturn($existing);
    $conversations->shouldNotReceive('save');

    $handler = new CreateConversationForMatchHandler($conversations);
    $conversation = $handler->handle(new CreateConversationForMatchCommand($matchId->toString()));

    expect($conversation->id()->equals($existing->id()))->toBeTrue();
});
