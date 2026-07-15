<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Media\Domain\Entities\Media as DomainMedia;
use App\Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use App\Modules\Media\Infrastructure\Persistence\Eloquent\Models\Media as EloquentMedia;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentMediaRepository implements MediaRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainMedia $media): void
    {
        EloquentMedia::query()->updateOrCreate(
            ['id' => $media->id()->toString()],
            [
                'owner_user_id' => $media->ownerUserId()->toString(),
                'disk' => $media->disk(),
                'path' => $media->path(),
                'mime_type' => $media->mimeType(),
                'size_bytes' => $media->sizeBytes(),
                'created_at' => $media->createdAt(),
            ],
        );
    }

    public function findById(Id $id): ?DomainMedia
    {
        $model = EloquentMedia::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(EloquentMedia $model): DomainMedia
    {
        return DomainMedia::reconstitute(
            id: Id::fromString($model->id),
            ownerUserId: Id::fromString($model->owner_user_id),
            disk: $model->disk,
            path: $model->path,
            mimeType: $model->mime_type,
            sizeBytes: $model->size_bytes,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
