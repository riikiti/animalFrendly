<?php

declare(strict_types=1);

namespace App\Modules\Admin\Application\Queries\GetAdminSummary;

use App\Modules\Marketplace\Domain\Repositories\BreederRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\DisputeRepositoryInterface;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;

/**
 * Admin — тонкий слой поверх остальных модулей: только агрегирует чтения из Moderation/
 * Shelter/Marketplace, ничего не хранит и не мутирует сам, см. docs/plan/01-modules.md.
 */
final class GetAdminSummaryHandler
{
    public function __construct(
        private readonly ReportRepositoryInterface $reports,
        private readonly ShelterRepositoryInterface $shelters,
        private readonly DisputeRepositoryInterface $disputes,
        private readonly BreederRepositoryInterface $breeders,
    ) {}

    /**
     * @return array{pending_reports: int, pending_shelter_verifications: int, pending_breeder_verifications: int, open_disputes: int}
     */
    public function handle(GetAdminSummaryQuery $query): array
    {
        return [
            'pending_reports' => $this->reports->countPending(),
            'pending_shelter_verifications' => $this->shelters->countPendingVerification(),
            'pending_breeder_verifications' => $this->breeders->countPendingVerification(),
            'open_disputes' => $this->disputes->countOpen(),
        ];
    }
}
