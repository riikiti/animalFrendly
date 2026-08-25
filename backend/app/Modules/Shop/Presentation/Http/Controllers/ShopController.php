<?php

declare(strict_types=1);

namespace App\Modules\Shop\Presentation\Http\Controllers;

use App\Modules\Shop\Application\Contracts\MediaUploaderInterface;
use App\Modules\Shop\Application\Services\CartService;
use App\Modules\Shop\Domain\Entities\Product;
use App\Modules\Shop\Domain\Exceptions\CannotBuyOwnProductException;
use App\Modules\Shop\Domain\Exceptions\CartFromSingleSellerException;
use App\Modules\Shop\Domain\Exceptions\ProductNotAvailableException;
use App\Modules\Shop\Domain\Exceptions\ProductNotFoundException;
use App\Modules\Shop\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Shop\Presentation\Http\Requests\AddToCartRequest;
use App\Modules\Shop\Presentation\Http\Requests\SaveProductRequest;
use App\Modules\Shop\Presentation\Http\Requests\UploadProductPhotoRequest;
use App\Modules\Shop\Presentation\Http\Resources\CategoryResource;
use App\Modules\Shop\Presentation\Http\Resources\ProductResource;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class ShopController
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductRepositoryInterface $products,
        private readonly CartService $cart,
    ) {}

    public function categories(): JsonResponse
    {
        return response()->json(['data' => CategoryResource::collection($this->categories->all())]);
    }

    public function products(Request $request): JsonResponse
    {
        $categorySlug = $request->string('category')->toString();
        $category = $categorySlug === '' ? null : $this->categories->findBySlug($categorySlug);

        return response()->json([
            'data' => ProductResource::collection($this->products->listPublished(
                $category?->id(),
                $request->string('q')->toString() ?: null,
                min($request->integer('per_page', 30), 60),
            )),
        ]);
    }

    public function product(string $id): JsonResponse
    {
        $product = $this->products->findById(Id::fromString($id));

        if ($product === null) {
            abort(404, 'Товар не найден.');
        }

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function myProducts(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ProductResource::collection(
                $this->products->listBySeller(Id::fromString($this->userId($request))),
            ),
        ]);
    }

    public function createProduct(SaveProductRequest $request): JsonResponse
    {
        $product = Product::create(
            $this->products->nextIdentity(),
            Id::fromString($this->userId($request)),
            Id::fromString($request->string('category_id')->toString()),
            $request->string('title')->toString(),
            $request->string('description')->toString() ?: null,
            Money::fromMinorUnits($request->integer('price_amount')),
            $request->integer('stock'),
            $request->string('photo_url')->toString() ?: null,
        );

        // Товар со складским остатком сразу уходит в витрину: отдельный черновик
        // продавцу на этом этапе не нужен.
        if ($product->stock() > 0) {
            $product->publish();
        }

        $this->products->save($product);

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function updateProduct(string $id, SaveProductRequest $request): JsonResponse
    {
        $product = $this->ownedProduct($id, $request);

        $product->update(
            $request->string('title')->toString(),
            $request->string('description')->toString() ?: null,
            Money::fromMinorUnits($request->integer('price_amount')),
            $request->integer('stock'),
            Id::fromString($request->string('category_id')->toString()),
            $request->string('photo_url')->toString() ?: null,
        );

        $this->products->save($product);

        return response()->json(['data' => new ProductResource($product)]);
    }

    /**
     * Фото товара: карточка держит одну картинку, повторная загрузка её заменяет.
     */
    public function uploadProductPhoto(
        string $id,
        UploadProductPhotoRequest $request,
        MediaUploaderInterface $uploader,
    ): JsonResponse {
        $product = $this->ownedProduct($id, $request);
        $photo = $request->file('photo');

        if (! $photo instanceof UploadedFile) {
            abort(422, 'Файл не передан.');
        }

        $uploaded = $uploader->upload($photo, Id::fromString($this->userId($request)));

        $product->update(
            $product->title(),
            $product->description(),
            $product->price(),
            $product->stock(),
            $product->categoryId(),
            $uploaded->url,
        );
        $this->products->save($product);

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function archiveProduct(string $id, Request $request): JsonResponse
    {
        $product = $this->ownedProduct($id, $request);
        $product->archive();
        $this->products->save($product);

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function cart(Request $request): JsonResponse
    {
        return response()->json($this->cartPayload(Id::fromString($this->userId($request)), $request));
    }

    public function addToCart(AddToCartRequest $request): JsonResponse
    {
        $userId = Id::fromString($this->userId($request));

        try {
            $this->cart->add(
                $userId,
                Id::fromString($request->string('product_id')->toString()),
                $request->integer('quantity', 1),
            );
        } catch (ProductNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (CannotBuyOwnProductException|CartFromSingleSellerException|ProductNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->cartPayload($userId, $request));
    }

    public function updateCartItem(string $productId, Request $request): JsonResponse
    {
        $userId = Id::fromString($this->userId($request));

        try {
            $this->cart->setQuantity($userId, Id::fromString($productId), $request->integer('quantity'));
        } catch (ProductNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->cartPayload($userId, $request));
    }

    public function removeCartItem(string $productId, Request $request): JsonResponse
    {
        $userId = Id::fromString($this->userId($request));
        $this->cart->remove($userId, Id::fromString($productId));

        return response()->json($this->cartPayload($userId, $request));
    }

    /**
     * @return array{data: array{items: array<int, array<string, mixed>>, seller_id: string|null, total_amount: int, currency: string}}
     */
    private function cartPayload(Id $userId, Request $request): array
    {
        $contents = $this->cart->contents($userId);

        return [
            'data' => [
                'items' => array_map(
                    static fn (array $item): array => [
                        'product' => (new ProductResource($item['product']))->toArray($request),
                        'quantity' => $item['quantity'],
                    ],
                    $contents['items'],
                ),
                'seller_id' => $contents['seller_id'],
                'total_amount' => $contents['total']->minorUnits,
                'currency' => $contents['total']->currency,
            ],
        ];
    }

    private function ownedProduct(string $id, Request $request): Product
    {
        $product = $this->products->findById(Id::fromString($id));

        if ($product === null) {
            abort(404, 'Товар не найден.');
        }

        if ($product->sellerId()->toString() !== $this->userId($request)) {
            abort(403, 'Это чужой товар.');
        }

        return $product;
    }

    private function userId(Request $request): string
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return (string) $user->getAuthIdentifier();
    }
}
