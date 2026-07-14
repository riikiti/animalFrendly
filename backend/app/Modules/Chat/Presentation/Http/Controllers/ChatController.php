<?php

declare(strict_types=1);

namespace App\Modules\Chat\Presentation\Http\Controllers;

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
use App\Modules\Chat\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Domain\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Domain\Exceptions\MatchNotFoundException;
use App\Modules\Chat\Presentation\Http\Requests\StoreMessageRequest;
use App\Modules\Chat\Presentation\Http\Resources\ConversationResource;
use App\Modules\Chat\Presentation\Http\Resources\MessageResource;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ChatController
{
    public function index(Request $request, ListMyConversationsHandler $handler): JsonResponse
    {
        $conversations = $handler->handle(new ListMyConversationsQuery($this->authenticatedUserId($request)));

        return response()->json(['data' => ConversationResource::collection($conversations)]);
    }

    public function conversationForMatch(
        string $matchId,
        Request $request,
        GetConversationForMatchHandler $handler,
    ): JsonResponse {
        try {
            $conversation = $handler->handle(new GetConversationForMatchQuery(
                actingUserId: $this->authenticatedUserId($request),
                matchId: $matchId,
            ));
        } catch (MatchNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConversationAccessDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new ConversationResource($conversation)]);
    }

    public function conversationForAdoptionRequest(
        string $adoptionRequestId,
        Request $request,
        GetConversationForAdoptionRequestHandler $handler,
    ): JsonResponse {
        try {
            $conversation = $handler->handle(new GetConversationForAdoptionRequestQuery(
                actingUserId: $this->authenticatedUserId($request),
                adoptionRequestId: $adoptionRequestId,
            ));
        } catch (AdoptionRequestNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (ConversationAccessDeniedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new ConversationResource($conversation)]);
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
