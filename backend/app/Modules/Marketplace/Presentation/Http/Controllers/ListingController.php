<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Marketplace\Application\Commands\ArchiveListing\ArchiveListingCommand;
use App\Modules\Marketplace\Application\Commands\ArchiveListing\ArchiveListingHandler;
use App\Modules\Marketplace\Application\Commands\CreateListing\CreateListingCommand;
use App\Modules\Marketplace\Application\Commands\CreateListing\CreateListingHandler;
use App\Modules\Marketplace\Application\Commands\PublishListing\PublishListingCommand;
use App\Modules\Marketplace\Application\Commands\PublishListing\PublishListingHandler;
use App\Modules\Marketplace\Application\Queries\ListMyListings\ListMyListingsHandler;
use App\Modules\Marketplace\Application\Queries\ListMyListings\ListMyListingsQuery;
use App\Modules\Marketplace\Application\Queries\ListPublishedListings\ListPublishedListingsHandler;
use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotAvailableException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotFoundException;
use App\Modules\Marketplace\Domain\Exceptions\NotListingOwnerException;
use App\Modules\Marketplace\Presentation\Http\Requests\StoreListingRequest;
use App\Modules\Marketplace\Presentation\Http\Resources\ListingResource;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListingController
{
    public function index(ListPublishedListingsHandler $handler, PetRepositoryInterface $pets): JsonResponse
    {
        $data = array_map(
            fn (Listing $listing) => $this->buildResource($listing, $pets),
            $handler->handle(),
        );

        return response()->json(['data' => $data]);
    }

    public function mine(Request $request, ListMyListingsHandler $handler, PetRepositoryInterface $pets): JsonResponse
    {
        $listings = $handler->handle(new ListMyListingsQuery($this->authenticatedUserId($request)));

        $data = array_map(
            fn (Listing $listing) => $this->buildResource($listing, $pets),
            $listings,
        );

        return response()->json(['data' => $data]);
    }

    public function store(
        StoreListingRequest $request,
        CreateListingHandler $handler,
        PetRepositoryInterface $pets,
    ): JsonResponse {
        try {
            $result = $handler->handle(new CreateListingCommand(
                sellerId: $this->authenticatedUserId($request),
                speciesId: $request->integer('species_id'),
                breedId: $request->integer('breed_id') ?: null,
                name: $request->string('name')->toString(),
                sex: $request->string('sex')->toString(),
                birthdate: $request->string('birthdate')->toString() ?: null,
                description: $request->string('description')->toString() ?: null,
                isVaccinated: $request->boolean('is_vaccinated'),
                priceAmount: $request->integer('price_amount'),
                currency: $request->string('currency')->toString() ?: 'RUB',
                parentPetId: $request->string('parent_pet_id')->toString() ?: null,
            ));
        } catch (PetNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (PetNotOwnedByActorException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => $this->buildResource($result->listing, $pets, $result->pet),
        ], 201);
    }

    public function publish(string $listingId, Request $request, PublishListingHandler $handler, PetRepositoryInterface $pets): JsonResponse
    {
        try {
            $listing = $handler->handle(new PublishListingCommand($listingId, $this->authenticatedUserId($request)));
        } catch (ListingNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotListingOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ListingNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->buildResource($listing, $pets)]);
    }

    public function archive(string $listingId, Request $request, ArchiveListingHandler $handler, PetRepositoryInterface $pets): JsonResponse
    {
        try {
            $listing = $handler->handle(new ArchiveListingCommand($listingId, $this->authenticatedUserId($request)));
        } catch (ListingNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (NotListingOwnerException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (ListingNotAvailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->buildResource($listing, $pets)]);
    }

    private function buildResource(Listing $listing, PetRepositoryInterface $pets, ?Pet $pet = null): ListingResource
    {
        $pet ??= $pets->findById($listing->petId());
        $seller = IdentityUser::query()->find($listing->sellerId()->toString());
        $parent = $pet?->parentId() !== null ? $pets->findById($pet->parentId()) : null;

        return new ListingResource([
            'listing' => $listing,
            'pet' => $pet,
            'parent_name' => $parent?->name(),
            'seller_name' => $seller?->name,
            'seller_avatar_url' => $seller?->avatar_url,
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
