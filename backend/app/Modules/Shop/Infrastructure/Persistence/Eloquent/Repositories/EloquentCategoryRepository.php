<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shop\Domain\Entities\Category;
use App\Modules\Shop\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopCategory;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function all(): array
    {
        return ShopCategory::query()
            ->orderBy('position')
            ->get()
            ->map(fn (ShopCategory $model): Category => $this->toDomain($model))
            ->all();
    }

    public function findById(Id $id): ?Category
    {
        $model = ShopCategory::query()->find($id->toString());

        return $model === null ? null : $this->toDomain($model);
    }

    public function findBySlug(string $slug): ?Category
    {
        $model = ShopCategory::query()->where('slug', $slug)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    private function toDomain(ShopCategory $model): Category
    {
        return new Category(
            Id::fromString($model->id),
            $model->slug,
            $model->name,
            $model->position,
        );
    }
}
