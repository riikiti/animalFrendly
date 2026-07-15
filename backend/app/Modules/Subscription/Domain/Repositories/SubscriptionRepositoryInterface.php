<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Repositories;

use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

interface SubscriptionRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Subscription $subscription): void;

    public function findById(Id $id): ?Subscription;

    /**
     * Подписка пользователя в незавершённом состоянии (pending_payment/active/past_due) —
     * используется, чтобы не дать оформить вторую подписку поверх текущей.
     */
    public function findCurrentForUser(Id $userId): ?Subscription;

    /**
     * Только активная (оплаченная и не истёкшая) подписка — источник тарифных лимитов.
     */
    public function findActiveForUser(Id $userId): ?Subscription;

    /**
     * Active, автопродление включено, период истёк — кандидаты на попытку автосписания.
     *
     * @return list<Subscription>
     */
    public function findDueForBilling(DateTimeImmutable $now): array;

    /**
     * Active, автопродление отключено (отмена), период истёк — доступ пора завершить без
     * попытки списания.
     *
     * @return list<Subscription>
     */
    public function findEndedWithoutRenewal(DateTimeImmutable $now): array;

    /**
     * Все подписки в статусе past_due — джоба сама решает (по grace-периоду), для какой из них
     * ещё повторить попытку списания, а для какой пора завершить доступ.
     *
     * @return list<Subscription>
     */
    public function findPastDue(): array;
}
