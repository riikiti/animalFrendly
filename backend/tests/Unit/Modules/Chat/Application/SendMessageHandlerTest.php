<?php

declare(strict_types=1);

use App\Modules\Chat\Application\Commands\SendMessage\SendMessageCommand;
use App\Modules\Chat\Application\Commands\SendMessage\SendMessageHandler;
use App\Modules\Chat\Application\Services\AdoptionRequestParticipantGuard;
use App\Modules\Chat\Application\Services\ConversationAccessGuard;
use App\Modules\Chat\Application\Services\MatchParticipantGuard;
use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Chat\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Matching\Domain\Entities\PetMatch;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Contracts\Events\Dispatcher;

it('dispatches MessageSent with the message body when a recipient can be resolved', function (): void {
    $conversationId = Id::generate();
    $matchId = Id::generate();
    $senderId = Id::generate();
    $recipientId = Id::generate();
    $senderPetId = Id::generate();
    $recipientPetId = Id::generate();

    $conversation = Conversation::createForMatch($conversationId, $matchId);
    $match = PetMatch::create(Id::generate(), $senderPetId, $recipientPetId);

    $senderPet = Pet::create($senderPetId, $senderId, 1, null, 'Отправитель', PetSex::Male, null, PetPurpose::Social, null, false);
    $recipientPet = Pet::create($recipientPetId, $recipientId, 1, null, 'Получатель', PetSex::Male, null, PetPurpose::Social, null, false);

    $conversations = Mockery::mock(ConversationRepositoryInterface::class);
    $conversations->shouldReceive('findById')->once()->andReturn($conversation);

    $messages = Mockery::mock(MessageRepositoryInterface::class);
    $messages->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $messages->shouldReceive('save')->once();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with(Mockery::on(fn (Id $id) => $id->equals($senderPetId)))->andReturn($senderPet);
    $pets->shouldReceive('findById')->with(Mockery::on(fn (Id $id) => $id->equals($recipientPetId)))->andReturn($recipientPet);

    $matches = Mockery::mock(PetMatchRepositoryInterface::class);
    $matches->shouldReceive('findById')->andReturn($match);

    $matchGuard = new MatchParticipantGuard($matches, $pets);

    $adoptionRequests = Mockery::mock(AdoptionRequestRepositoryInterface::class);
    $shelterAnimals = Mockery::mock(ShelterAnimalRepositoryInterface::class);
    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $adoptionGuard = new AdoptionRequestParticipantGuard($adoptionRequests, $shelterAnimals, $shelters);

    $accessGuard = new ConversationAccessGuard($matchGuard, $adoptionGuard);

    $events = Mockery::mock(Dispatcher::class);
    $events->shouldReceive('dispatch')->once()->with(Mockery::on(
        fn (MessageSent $event) => $event->body === 'Привет!'
            && $event->senderUserId->equals($senderId)
            && $event->recipientUserId->equals($recipientId)
            && $event->conversationId->equals($conversationId),
    ));

    $handler = new SendMessageHandler($conversations, $messages, $accessGuard, $events);
    $message = $handler->handle(new SendMessageCommand($senderId->toString(), $conversationId->toString(), 'Привет!'));

    expect($message->body())->toBe('Привет!');
});
