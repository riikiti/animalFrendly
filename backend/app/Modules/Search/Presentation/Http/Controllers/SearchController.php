<?php

declare(strict_types=1);

namespace App\Modules\Search\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsHandler;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsQuery;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsHandler;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsQuery;
use App\Modules\Search\Presentation\Http\Requests\SearchListingsRequest;
use App\Modules\Search\Presentation\Http\Requests\SearchPetsRequest;
use App\Modules\Search\Presentation\Http\Resources\ListingSearchResultResource;
use App\Modules\Search\Presentation\Http\Resources\PetSearchResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController
{
    public function pets(SearchPetsRequest $request, SearchPetsHandler $handler): JsonResponse
    {
        $page = $handler->handle(new SearchPetsQuery(
            actingUserId: $this->authenticatedUserId($request),
            speciesId: $request->integer('species_id') ?: null,
            breedId: $request->integer('breed_id') ?: null,
            sex: $request->string('sex')->toString() ?: null,
            purpose: $request->string('purpose')->toString() ?: null,
            city: $request->string('city')->toString() ?: null,
            isVaccinated: $request->has('is_vaccinated') ? $request->boolean('is_vaccinated') : null,
            radiusKm: $request->float('radius_km') ?: null,
            page: $request->integer('page') ?: 1,
            perPage: $request->integer('per_page') ?: 20,
        ));

        return response()->json([
            'data' => PetSearchResultResource::collection($page->items),
            'meta' => ['total' => $page->total, 'page' => $page->page, 'per_page' => $page->perPage],
        ]);
    }

    public function listings(SearchListingsRequest $request, SearchListingsHandler $handler): JsonResponse
    {
        $page = $handler->handle(new SearchListingsQuery(
            actingUserId: $this->authenticatedUserId($request),
            speciesId: $request->integer('species_id') ?: null,
            breedId: $request->integer('breed_id') ?: null,
            city: $request->string('city')->toString() ?: null,
            minPriceAmount: $request->integer('min_price_amount') ?: null,
            maxPriceAmount: $request->integer('max_price_amount') ?: null,
            radiusKm: $request->float('radius_km') ?: null,
            page: $request->integer('page') ?: 1,
            perPage: $request->integer('per_page') ?: 20,
        ));

        return response()->json([
            'data' => ListingSearchResultResource::collection($page->items),
            'meta' => ['total' => $page->total, 'page' => $page->page, 'per_page' => $page->perPage],
        ]);
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
