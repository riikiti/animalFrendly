<?php

declare(strict_types=1);

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Enums\ShelterVerificationStatus;
use App\Shared\Domain\ValueObjects\Id;

it('registers a shelter as pending verification', function (): void {
    $shelter = Shelter::register(Id::generate(), Id::generate(), 'Добрые лапы', null, null);

    expect($shelter->verificationStatus())->toBe(ShelterVerificationStatus::Pending)
        ->and($shelter->isVerified())->toBeFalse();
});

it('rejects an empty legal name', function (): void {
    Shelter::register(Id::generate(), Id::generate(), '   ', null, null);
})->throws(InvalidArgumentException::class);

it('becomes verified after a moderator verifies it', function (): void {
    $shelter = Shelter::register(Id::generate(), Id::generate(), 'Добрые лапы', null, null);
    $moderatorId = Id::generate();

    $shelter->verify($moderatorId);

    expect($shelter->isVerified())->toBeTrue()
        ->and($shelter->verifiedBy()?->equals($moderatorId))->toBeTrue()
        ->and($shelter->verifiedAt())->not->toBeNull();
});
