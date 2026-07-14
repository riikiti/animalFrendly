<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Entities;

use App\Modules\Marketplace\Domain\Enums\ListingStatus;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotAvailableException;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class Listing
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $sellerId,
        private readonly Id $petId,
        private readonly Money $price,
        private ListingStatus $status,
    ) {}

    public static function create(Id $id, Id $sellerId, Id $petId, Money $price): self
    {
        return new self($id, $sellerId, $petId, $price, ListingStatus::Draft);
    }

    public static function reconstitute(
        Id $id,
        Id $sellerId,
        Id $petId,
        Money $price,
        ListingStatus $status,
    ): self {
        return new self($id, $sellerId, $petId, $price, $status);
    }

    public function publish(): void
    {
        if ($this->status !== ListingStatus::Draft) {
            throw ListingNotAvailableException::create();
        }

        $this->status = ListingStatus::Published;
    }

    public function archive(): void
    {
        if (! in_array($this->status, [ListingStatus::Draft, ListingStatus::Published], true)) {
            throw ListingNotAvailableException::create();
        }

        $this->status = ListingStatus::Archived;
    }

    public function reserve(): void
    {
        if ($this->status !== ListingStatus::Published) {
            throw ListingNotAvailableException::create();
        }

        $this->status = ListingStatus::Reserved;
    }

    public function backToPublished(): void
    {
        if ($this->status !== ListingStatus::Reserved) {
            throw ListingNotAvailableException::create();
        }

        $this->status = ListingStatus::Published;
    }

    public function markSold(): void
    {
        if ($this->status !== ListingStatus::Reserved) {
            throw ListingNotAvailableException::create();
        }

        $this->status = ListingStatus::Sold;
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function sellerId(): Id
    {
        return $this->sellerId;
    }

    public function petId(): Id
    {
        return $this->petId;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function status(): ListingStatus
    {
        return $this->status;
    }
}
