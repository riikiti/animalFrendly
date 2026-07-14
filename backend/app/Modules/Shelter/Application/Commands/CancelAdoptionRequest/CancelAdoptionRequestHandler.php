<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\CancelAdoptionRequest;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\NotAdoptionRequesterException;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CancelAdoptionRequestHandler
{
    public function __construct(private readonly AdoptionRequestRepositoryInterface $requests) {}

    public function handle(CancelAdoptionRequestCommand $command): AdoptionRequest
    {
        $request = $this->requests->findById(Id::fromString($command->adoptionRequestId));

        if ($request === null) {
            throw AdoptionRequestNotFoundException::forId($command->adoptionRequestId);
        }

        if (! $request->requesterUserId()->equals(Id::fromString($command->actingUserId))) {
            throw NotAdoptionRequesterException::create();
        }

        $request->cancel();
        $this->requests->save($request);

        return $request;
    }
}
