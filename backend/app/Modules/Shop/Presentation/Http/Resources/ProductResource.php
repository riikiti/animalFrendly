<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Resources;

use App\Modules\Shop\Domain\Entities\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        return [
            'id' => $product->id()->toString(),
            'seller_id' => $product->sellerId()->toString(),
            'category_id' => $product->categoryId()->toString(),
            'title' => $product->title(),
            'description' => $product->description(),
            'price_amount' => $product->price()->minorUnits,
            'currency' => $product->price()->currency,
            'stock' => $product->stock(),
            'status' => $product->status()->value,
            'photo_url' => $product->photoUrl(),
        ];
    }
}
