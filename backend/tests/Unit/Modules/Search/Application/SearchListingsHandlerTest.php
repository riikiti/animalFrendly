<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsHandler;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsQuery;
use App\Shared\Domain\ValueObjects\Id;

it('passes the acting user coordinates to the listings index', function (): void {
    $userId = Id::generate();
    $user = User::register($userId, PhoneNumber::fromString('+79261234567'), 'hash', AccountType::Owner, true);
    $user->setLocation('Адрес', 'Казань', 55.796, 49.106);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);

    $page = new SearchResultPage([], 0, 1, 20);
    $index = Mockery::mock(ListingSearchIndexInterface::class);
    $index->shouldReceive('search')->once()->with(
        Mockery::type(SearchListingsQuery::class),
        55.796,
        49.106,
    )->andReturn($page);

    $handler = new SearchListingsHandler($index, $users);
    $result = $handler->handle(new SearchListingsQuery(actingUserId: $userId->toString()));

    expect($result)->toBe($page);
});
