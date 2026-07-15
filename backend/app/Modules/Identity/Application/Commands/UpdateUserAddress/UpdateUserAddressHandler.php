<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\UpdateUserAddress;

use App\Modules\Identity\Application\Contracts\GeocoderInterface;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Exceptions\UserNotFoundException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class UpdateUserAddressHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly GeocoderInterface $geocoder,
    ) {}

    public function handle(UpdateUserAddressCommand $command): User
    {
        $user = $this->users->findById(Id::fromString($command->userId));

        if ($user === null) {
            throw UserNotFoundException::forId($command->userId);
        }

        if ($command->address === null || trim($command->address) === '') {
            $user->setLocation(null, null, null, null);
            $this->users->save($user);

            return $user;
        }

        $geocoded = $this->geocoder->geocode($command->address);

        $user->setLocation(
            $command->address,
            $geocoded?->city,
            $geocoded?->latitude,
            $geocoded?->longitude,
        );
        $this->users->save($user);

        return $user;
    }
}
