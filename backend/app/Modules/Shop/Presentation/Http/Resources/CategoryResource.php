<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Resources;

use App\Modules\Shop\Domain\Entities\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->id()->toString(),
            'slug' => $category->slug(),
            'name' => $category->name(),
        ];
    }
}
