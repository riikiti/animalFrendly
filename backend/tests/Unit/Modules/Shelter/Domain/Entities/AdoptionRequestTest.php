<?php

declare(strict_types=1);

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Enums\AdoptionRequestStatus;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestAlreadyDecidedException;
use App\Shared\Domain\ValueObjects\Id;

it('is created as pending', function (): void {
    $request = AdoptionRequest::create(Id::generate(), Id::generate(), Id::generate(), 'Хочу забрать');

    expect($request->status())->toBe(AdoptionRequestStatus::Pending);
});

it('can be approved once', function (): void {
    $request = AdoptionRequest::create(Id::generate(), Id::generate(), Id::generate(), null);
    $moderatorId = Id::generate();

    $request->approve($moderatorId);

    expect($request->status())->toBe(AdoptionRequestStatus::Approved)
        ->and($request->decidedBy()?->equals($moderatorId))->toBeTrue();
});

it('cannot be decided twice', function (): void {
    $request = AdoptionRequest::create(Id::generate(), Id::generate(), Id::generate(), null);
    $request->approve(Id::generate());

    $request->reject(Id::generate());
})->throws(AdoptionRequestAlreadyDecidedException::class);

it('can be cancelled by the requester while pending', function (): void {
    $request = AdoptionRequest::create(Id::generate(), Id::generate(), Id::generate(), null);

    $request->cancel();

    expect($request->status())->toBe(AdoptionRequestStatus::Cancelled);
});
