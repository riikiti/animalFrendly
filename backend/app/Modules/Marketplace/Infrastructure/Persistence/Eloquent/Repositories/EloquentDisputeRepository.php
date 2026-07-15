<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Marketplace\Domain\Entities\Dispute as DomainDispute;
use App\Modules\Marketplace\Domain\Enums\DisputeResolution;
use App\Modules\Marketplace\Domain\Repositories\DisputeRepositoryInterface;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models\Dispute as EloquentDispute;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentDisputeRepository implements DisputeRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainDispute $dispute): void
    {
        EloquentDispute::query()->updateOrCreate(
            ['id' => $dispute->id()->toString()],
            [
                'order_id' => $dispute->orderId()->toString(),
                'opened_by' => $dispute->openedBy()->toString(),
                'reason' => $dispute->reason(),
                'resolution' => $dispute->resolution()?->value,
                'resolved_by' => $dispute->resolvedBy()?->toString(),
                'resolved_at' => $dispute->resolvedAt(),
            ],
        );
    }

    public function findById(Id $id): ?DomainDispute
    {
        $model = EloquentDispute::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderId(Id $orderId): ?DomainDispute
    {
        $model = EloquentDispute::query()->where('order_id', $orderId->toString())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function countOpen(): int
    {
        return EloquentDispute::query()->whereNull('resolved_at')->count();
    }

    private function toDomain(EloquentDispute $model): DomainDispute
    {
        return DomainDispute::reconstitute(
            id: Id::fromString($model->id),
            orderId: Id::fromString($model->order_id),
            openedBy: Id::fromString($model->opened_by),
            reason: $model->reason,
            resolution: $model->resolution !== null ? DisputeResolution::from($model->resolution) : null,
            resolvedBy: $model->resolved_by !== null ? Id::fromString($model->resolved_by) : null,
            resolvedAt: $model->resolved_at?->toDateTimeImmutable(),
        );
    }
}
