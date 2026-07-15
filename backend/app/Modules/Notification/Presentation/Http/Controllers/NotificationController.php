<?php

declare(strict_types=1);

namespace App\Modules\Notification\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Notification\Application\Commands\MarkAllNotificationsRead\MarkAllNotificationsReadCommand;
use App\Modules\Notification\Application\Commands\MarkAllNotificationsRead\MarkAllNotificationsReadHandler;
use App\Modules\Notification\Application\Commands\MarkNotificationRead\MarkNotificationReadCommand;
use App\Modules\Notification\Application\Commands\MarkNotificationRead\MarkNotificationReadHandler;
use App\Modules\Notification\Application\Commands\RegisterDeviceToken\RegisterDeviceTokenCommand;
use App\Modules\Notification\Application\Commands\RegisterDeviceToken\RegisterDeviceTokenHandler;
use App\Modules\Notification\Application\Commands\UnregisterDeviceToken\UnregisterDeviceTokenCommand;
use App\Modules\Notification\Application\Commands\UnregisterDeviceToken\UnregisterDeviceTokenHandler;
use App\Modules\Notification\Application\Queries\CountUnread\CountUnreadHandler;
use App\Modules\Notification\Application\Queries\CountUnread\CountUnreadQuery;
use App\Modules\Notification\Application\Queries\ListNotifications\ListNotificationsHandler;
use App\Modules\Notification\Application\Queries\ListNotifications\ListNotificationsQuery;
use App\Modules\Notification\Domain\Exceptions\NotificationNotFoundException;
use App\Modules\Notification\Domain\Exceptions\NotificationNotOwnedByActorException;
use App\Modules\Notification\Presentation\Http\Requests\RegisterDeviceTokenRequest;
use App\Modules\Notification\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function index(Request $request, ListNotificationsHandler $handler): JsonResponse
    {
        $notifications = $handler->handle(new ListNotificationsQuery($this->authenticatedUserId($request)));

        return response()->json(['data' => NotificationResource::collection($notifications)]);
    }

    public function unreadCount(Request $request, CountUnreadHandler $handler): JsonResponse
    {
        $count = $handler->handle(new CountUnreadQuery($this->authenticatedUserId($request)));

        return response()->json(['data' => ['count' => $count]]);
    }

    public function markRead(string $notificationId, Request $request, MarkNotificationReadHandler $handler): JsonResponse
    {
        try {
            $notification = $handler->handle(new MarkNotificationReadCommand(
                notificationId: $notificationId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (NotificationNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotificationNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => new NotificationResource($notification)]);
    }

    public function markAllRead(Request $request, MarkAllNotificationsReadHandler $handler): JsonResponse
    {
        $handler->handle(new MarkAllNotificationsReadCommand($this->authenticatedUserId($request)));

        return response()->json(['data' => ['ok' => true]]);
    }

    public function registerDeviceToken(
        RegisterDeviceTokenRequest $request,
        RegisterDeviceTokenHandler $handler,
    ): JsonResponse {
        $handler->handle(new RegisterDeviceTokenCommand(
            actingUserId: $this->authenticatedUserId($request),
            platform: $request->string('platform')->toString(),
            fcmToken: $request->string('fcm_token')->toString(),
        ));

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function unregisterDeviceToken(string $fcmToken, UnregisterDeviceTokenHandler $handler): JsonResponse
    {
        $handler->handle(new UnregisterDeviceTokenCommand($fcmToken));

        return response()->json(['data' => ['ok' => true]]);
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
