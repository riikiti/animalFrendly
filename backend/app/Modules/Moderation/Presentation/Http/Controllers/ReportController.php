<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Moderation\Application\Commands\DismissReport\DismissReportCommand;
use App\Modules\Moderation\Application\Commands\DismissReport\DismissReportHandler;
use App\Modules\Moderation\Application\Commands\ReviewReport\ReviewReportCommand;
use App\Modules\Moderation\Application\Commands\ReviewReport\ReviewReportHandler;
use App\Modules\Moderation\Application\Commands\SubmitReport\SubmitReportCommand;
use App\Modules\Moderation\Application\Commands\SubmitReport\SubmitReportHandler;
use App\Modules\Moderation\Application\Queries\ListPendingReports\ListPendingReportsHandler;
use App\Modules\Moderation\Application\Queries\ListPendingReports\ListPendingReportsQuery;
use App\Modules\Moderation\Domain\Exceptions\InvalidReportStatusTransitionException;
use App\Modules\Moderation\Domain\Exceptions\ReportNotFoundException;
use App\Modules\Moderation\Presentation\Http\Requests\SubmitReportRequest;
use App\Modules\Moderation\Presentation\Http\Resources\ReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ReportController
{
    public function store(SubmitReportRequest $request, SubmitReportHandler $handler): JsonResponse
    {
        $report = $handler->handle(new SubmitReportCommand(
            reporterId: $this->authenticatedUserId($request),
            targetType: $request->string('target_type')->toString(),
            targetId: $request->string('target_id')->toString(),
            reason: $request->string('reason')->toString(),
            comment: $request->string('comment')->toString() ?: null,
        ));

        return response()->json(['data' => new ReportResource($report)], 201);
    }

    public function index(ListPendingReportsHandler $handler): JsonResponse
    {
        Gate::authorize('moderate-reports');

        $reports = $handler->handle(new ListPendingReportsQuery);

        return response()->json(['data' => ReportResource::collection($reports)]);
    }

    public function review(string $reportId, Request $request, ReviewReportHandler $handler): JsonResponse
    {
        Gate::authorize('moderate-reports');

        try {
            $report = $handler->handle(new ReviewReportCommand(
                reportId: $reportId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (ReportNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidReportStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new ReportResource($report)]);
    }

    public function dismiss(string $reportId, Request $request, DismissReportHandler $handler): JsonResponse
    {
        Gate::authorize('moderate-reports');

        try {
            $report = $handler->handle(new DismissReportCommand(
                reportId: $reportId,
                actingUserId: $this->authenticatedUserId($request),
            ));
        } catch (ReportNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (InvalidReportStatusTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new ReportResource($report)]);
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
