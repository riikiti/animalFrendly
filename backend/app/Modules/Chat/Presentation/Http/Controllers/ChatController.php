<?php

declare(strict_types=1);

namespace App\Modules\Chat\Presentation\Http\Controllers;

use App\Modules\Chat\Application\Commands\CreateConversationForDirectContact\CreateConversationForDirectContactCommand;
use App\Modules\Chat\Application\Commands\CreateConversationForDirectContact\CreateConversationForDirectContactHandler;
use App\Modules\Chat\Application\Commands\CreateConversationForShelterContact\CreateConversationForShelterContactCommand;
use App\Modules\Chat\Application\Commands\CreateConversationForShelterContact\CreateConversationForShelterContactHandler;
use App\Modules\Chat\Application\Commands\SendMessage\SendMessageCommand;
use App\Modules\Chat\Application\Commands\SendMessage\SendMessageHandler;
use App\Modules\Chat\Application\Queries\GetConversationForAdoptionRequest\GetConversationForAdoptionRequestHandler;
use App\Modules\Chat\Application\Queries\GetConversationForAdoptionRequest\GetConversationForAdoptionRequestQuery;
use App\Modules\Chat\Application\Queries\GetConversationForMatch\GetConversationForMatchHandler;
use App\Modules\Chat\Application\Queries\GetConversationForMatch\GetConversationForMatchQuery;
use App\Modules\Chat\Application\Queries\ListMessages\ListMessagesHandler;
use App\Modules\Chat\Application\Queries\ListMessages\ListMessagesQuery;
use App\Modules\Chat\Application\Queries\ListMyConversations\ListMyConversationsHandler;
use App\Modules\Chat\Application\Queries\ListMyConversations\ListMyConversationsQuery;
use App\Modules\Chat\Application\Services\AdoptionRequestParticipantGuard;
use App\Modules\Chat\Application\Services\ConversationAccessGuard;
use App\Modules\Chat\Application\Services\MatchParticipantGuard;
use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Domain\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Domain\Exceptions\MatchNotFoundException;
use App\Modules\Chat\Presentation\Http\Requests\StoreMessageRequest;
use App\Modules\Chat\Presentation\Http\Resources\ConversationResource;
use App\Modules\Chat\Presentation\Http\Resources\MessageResource;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ChatController
{
    public function index(
        Request $request,
        ListMyConversationsHandler $handler,
        ConversationAccessGuard $accessGuard,
    ): JsonResponse {
        $actingUserId = Id::fromString($this->authenticatedUserId($request));
        $conversations = $handler->handle(new ListMyConversationsQuery($actingUserId->toString()));

        $data = array_map(function (Conversation $conversation) use ($actingUserId, $accessGuard) {
            $counterpartId = $accessGuard->resolveRecipientId($conversation, $actingUserId);
            $counterpart = $counterpartId !== null ? IdentityUser::query()->find($counterpartId->toString()) : null;

            return new ConversationResource([
                'conversation' => $conversation,
                'counterpart_name' => $counterpart?->name,
                'counterpart_avatar_url' => $counterpart?->avatar_url,
            ]);
        }, $conversations);

        return response()->json(['data' => $data]);
    }

    public function conversationForShelter(
        string $shelterId,
        Request $request,
        CreateConversationForShelterContactHandler $handler,
    ): JsonResponse {
        $conversation = $handler->handle(new CreateConversationForShelterContactCommand(
            shelterId: $shelterId,
            initiatorUserId: $this->authenticatedUserId($request),
            shelterAnimalId: $request->string('shelter_animal_id')->toString() ?: null,
        ));

        return response()->json(['data' => new ConversationResource(['conversation' => $conversation])]);
    }

    public function conversationForUser(
        string $userId,
        Request $request,
        CreateConversationForDirectContactHandler $handler,
    ): JsonResponse {
        $conversation = $handler->handle(new CreateConversationForDirectContactCommand(
            recipientUserId: $userId,
            initiatorUserId: $this->authenticatedUserId($request),
        ));

        return response()->json(['data' => new ConversationResource(['conversation' => $conversation])]);
    }

    public function conversationForMatch(
        string $matchId,
        Request $request,
        GetConversationForMatchHandler $handler,
        MatchParticipantGuard $participantGuard,
        UserRepositoryInterface $users,
    ): JsonResponse {
        $actingUserId = Id::fromString($this->authenticatedUserId($request));

        try {
            $conversation = $handler->handle(new GetConversationForMatchQuery(
                actingUserId: $actingUserId->toString(),
                matchId: $matchId,
            ));
            $match = $participantGuard->assertParticipant(Id::fromString($matchId), $actingUserId);
        } catch (MatchNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConversationAccessDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $counterpartId = $participantGuard->otherPetOwnerId($match, $actingUserId);

        return response()->json(['data' => new ConversationResource([
            'conversation' => $conversation,
            ...$this->resolveCounterpartLocation($counterpartId, $users),
        ])]);
    }

    public function conversationForAdoptionRequest(
        string $adoptionRequestId,
        Request $request,
        GetConversationForAdoptionRequestHandler $handler,
        AdoptionRequestParticipantGuard $participantGuard,
        UserRepositoryInterface $users,
    ): JsonResponse {
        $actingUserId = Id::fromString($this->authenticatedUserId($request));

        try {
            $conversation = $handler->handle(new GetConversationForAdoptionRequestQuery(
                actingUserId: $actingUserId->toString(),
                adoptionRequestId: $adoptionRequestId,
            ));
            $adoptionRequest = $participantGuard->assertParticipant(Id::fromString($adoptionRequestId), $actingUserId);
        } catch (AdoptionRequestNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConversationAccessDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $counterpartId = $participantGuard->otherParticipantId($adoptionRequest, $actingUserId);

        return response()->json(['data' => new ConversationResource([
            'conversation' => $conversation,
            ...$this->resolveCounterpartLocation($counterpartId, $users),
        ])]);
    }

    /**
     * Точный адрес контрагента виден только участнику начатой беседы — до этого момента
     * показывать домашний адрес незнакомому человеку небезопасно, см. Search-фазу.
     *
     * @return array{counterpart_address: ?string, counterpart_location: ?array{lat: float, lng: float}}
     */
    private function resolveCounterpartLocation(?Id $counterpartId, UserRepositoryInterface $users): array
    {
        $counterpart = $counterpartId !== null ? $users->findById($counterpartId) : null;

        if ($counterpart === null || $counterpart->address() === null) {
            return ['counterpart_address' => null, 'counterpart_location' => null];
        }

        return [
            'counterpart_address' => $counterpart->address(),
            'counterpart_location' => $counterpart->hasCoordinates()
                ? ['lat' => (float) $counterpart->latitude(), 'lng' => (float) $counterpart->longitude()]
                : null,
        ];
    }

    public function messages(string $conversationId, Request $request, ListMessagesHandler $handler): JsonResponse
    {
        try {
            $messages = $handler->handle(new ListMessagesQuery(
                actingUserId: $this->authenticatedUserId($request),
                conversationId: $conversationId,
            ));
        } catch (ConversationNotFoundException|MatchNotFoundException|AdoptionRequestNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConversationAccessDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => MessageResource::collection($messages)]);
    }

    public function sendMessage(
        string $conversationId,
        StoreMessageRequest $request,
        SendMessageHandler $handler,
    ): JsonResponse {
        try {
            $message = $handler->handle(new SendMessageCommand(
                actingUserId: $this->authenticatedUserId($request),
                conversationId: $conversationId,
                body: $request->string('body')->toString(),
            ));
        } catch (ConversationNotFoundException|MatchNotFoundException|AdoptionRequestNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConversationAccessDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new MessageResource($message)], 201);
    }

    private function authenticatedUserId(Request $request): string
    {
        $user = $request->user();

        if (! $user instanceof IdentityUser) {
            abort(401);
        }

        return $user->id;
    }
}
