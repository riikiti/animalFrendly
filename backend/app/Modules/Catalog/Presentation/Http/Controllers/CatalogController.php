<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers;

use App\Modules\Catalog\Application\Queries\ListBreeds\ListBreedsHandler;
use App\Modules\Catalog\Application\Queries\ListBreeds\ListBreedsQuery;
use App\Modules\Catalog\Application\Queries\ListSpecies\ListSpeciesHandler;
use App\Modules\Catalog\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Catalog\Presentation\Http\Resources\BreedResource;
use App\Modules\Catalog\Presentation\Http\Resources\SpeciesResource;
use Illuminate\Http\JsonResponse;

final class CatalogController
{
    public function species(ListSpeciesHandler $handler): JsonResponse
    {
        return response()->json([
            'data' => SpeciesResource::collection($handler->handle()),
        ]);
    }

    public function breeds(string $speciesSlug, ListBreedsHandler $handler): JsonResponse
    {
        try {
            $breeds = $handler->handle(new ListBreedsQuery($speciesSlug));
        } catch (SpeciesNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json([
            'data' => BreedResource::collection($breeds),
        ]);
    }
}
