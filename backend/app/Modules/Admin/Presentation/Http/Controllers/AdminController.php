<?php

declare(strict_types=1);

namespace App\Modules\Admin\Presentation\Http\Controllers;

use App\Modules\Admin\Application\Queries\GetAdminSummary\GetAdminSummaryHandler;
use App\Modules\Admin\Application\Queries\GetAdminSummary\GetAdminSummaryQuery;
use App\Modules\Moderation\Application\Queries\ListRecentAuditLog\ListRecentAuditLogHandler;
use App\Modules\Moderation\Application\Queries\ListRecentAuditLog\ListRecentAuditLogQuery;
use App\Modules\Moderation\Presentation\Http\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class AdminController
{
    public function summary(GetAdminSummaryHandler $handler): JsonResponse
    {
        Gate::authorize('access-admin-panel');

        return response()->json(['data' => $handler->handle(new GetAdminSummaryQuery)]);
    }

    public function auditLog(ListRecentAuditLogHandler $handler): JsonResponse
    {
        Gate::authorize('access-admin-panel');

        return response()->json(['data' => AuditLogResource::collection($handler->handle(new ListRecentAuditLogQuery))]);
    }
}
