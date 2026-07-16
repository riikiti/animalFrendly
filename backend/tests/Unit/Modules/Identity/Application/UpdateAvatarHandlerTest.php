<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarCommand;
use App\Modules\Identity\Application\Commands\UpdateAvatar\UpdateAvatarHandler;
use App\Modules\Identity\Application\Contracts\MediaUploaderInterface;
use App\Modules\Identity\Application\Contracts\UploadedMedia;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Exceptions\UserNotFoundException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;

it('uploads and sets the avatar', function (): void {
    $userId = Id::generate();
    $user = User::register(
        id: $userId,
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(
        fn (User $u) => $u->avatarUrl() === 'https://cdn.example/avatar.jpg',
    ));

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldReceive('upload')->once()
        ->andReturn(new UploadedMedia(Id::generate()->toString(), 'https://cdn.example/avatar.jpg'));

    $handler = new UpdateAvatarHandler($users, $uploader);
    $handler->handle(new UpdateAvatarCommand($userId->toString(), UploadedFile::fake()->image('me.jpg')));
});

it('rejects uploading an avatar for a user that does not exist', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn(null);

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldNotReceive('upload');

    $handler = new UpdateAvatarHandler($users, $uploader);
    $handler->handle(new UpdateAvatarCommand(Id::generate()->toString(), UploadedFile::fake()->image('me.jpg')));
})->throws(UserNotFoundException::class);
