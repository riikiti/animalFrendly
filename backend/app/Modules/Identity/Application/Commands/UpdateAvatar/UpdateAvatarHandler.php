<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\UpdateAvatar;

use App\Modules\Identity\Application\Contracts\MediaUploaderInterface;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Exceptions\UserNotFoundException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class UpdateAvatarHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MediaUploaderInterface $mediaUploader,
    ) {}

    public function handle(UpdateAvatarCommand $command): User
    {
        $userId = Id::fromString($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::forId($command->userId);
        }

        $uploaded = $this->mediaUploader->upload($command->photo, $userId);
        $user->setAvatar($uploaded->url);
        $this->users->save($user);

        return $user;
    }
}
