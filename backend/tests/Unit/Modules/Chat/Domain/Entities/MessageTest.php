<?php

declare(strict_types=1);

use App\Modules\Chat\Domain\Entities\Message;
use App\Shared\Domain\ValueObjects\Id;

it('sends a message with trimmed body', function (): void {
    $message = Message::send(Id::generate(), Id::generate(), Id::generate(), '  Привет!  ');

    expect($message->body())->toBe('Привет!')
        ->and($message->readAt())->toBeNull();
});

it('rejects an empty message', function (): void {
    Message::send(Id::generate(), Id::generate(), Id::generate(), '   ');
})->throws(InvalidArgumentException::class);
