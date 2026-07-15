<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsHandler;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsQuery;
use App\Shared\Domain\ValueObjects\Id;

it('passes the acting user coordinates to the index when they have a location', function (): void {
    $userId = Id::generate();
    $user = User::register($userId, PhoneNumber::fromString('+79261234567'), 'hash', AccountType::Owner, true);
    $user->setLocation('Адрес', 'Москва', 55.755, 37.617);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);

    $page = new SearchResultPage([], 0, 1, 20);
    $index = Mockery::mock(PetSearchIndexInterface::class);
    $index->shouldReceive('search')->once()->with(
        Mockery::type(SearchPetsQuery::class),
        55.755,
        37.617,
    )->andReturn($page);

    $handler = new SearchPetsHandler($index, $users);
    $result = $handler->handle(new SearchPetsQuery(actingUserId: $userId->toString()));

    expect($result)->toBe($page);
});

it('passes null coordinates when the acting user has no location', function (): void {
    $userId = Id::generate();
    $user = User::register($userId, PhoneNumber::fromString('+79261234567'), 'hash', AccountType::Owner, true);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);

    $page = new SearchResultPage([], 0, 1, 20);
    $index = Mockery::mock(PetSearchIndexInterface::class);
    $index->shouldReceive('search')->once()->with(Mockery::type(SearchPetsQuery::class), null, null)->andReturn($page);

    $handler = new SearchPetsHandler($index, $users);
    $handler->handle(new SearchPetsQuery(actingUserId: $userId->toString()));
});
