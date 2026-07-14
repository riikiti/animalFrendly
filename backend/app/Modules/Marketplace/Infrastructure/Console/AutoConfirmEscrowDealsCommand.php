<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Console;

use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Почасовая джоба авто-подтверждения эскроу-сделок, см. docs/rules/04-payments-escrow.md.
 * Регистрируется в Scheduler в bootstrap/app.php.
 */
final class AutoConfirmEscrowDealsCommand extends Command
{
    protected $signature = 'deals:auto-confirm';

    protected $description = 'Переводит paid_escrow-сделки старше срока удержания в completed';

    public function handle(OrderRepositoryInterface $orders, DomainEventDispatcherInterface $events): int
    {
        $expired = $orders->findEscrowExpired();
        $confirmed = 0;

        foreach ($expired as $order) {
            $orderId = $order->id()->toString();

            $lock = Cache::lock("order:{$orderId}", 10);

            $lock->block(5, function () use ($orders, $events, $orderId, &$confirmed): void {
                DB::transaction(function () use ($orders, $events, $orderId, &$confirmed): void {
                    // Повторная загрузка под локом — защита от гонки с вебхуком/ручным
                    // подтверждением, которые могли поменять статус между findEscrowExpired()
                    // и этим моментом.
                    $fresh = $orders->findById(Id::fromString($orderId));

                    if ($fresh === null || $fresh->status() !== OrderStatus::PaidEscrow) {
                        return;
                    }

                    $fresh->autoConfirm();
                    $orders->save($fresh, null, 'auto_confirmed');

                    $payoutAmount = $fresh->payoutAmount();

                    if ($payoutAmount === null) {
                        throw InvalidOrderStatusTransitionException::create();
                    }

                    $events->dispatch(new OrderCompleted($fresh->id(), $fresh->sellerId(), $payoutAmount, new DateTimeImmutable));
                    $confirmed++;
                });
            });
        }

        $this->info("Авто-подтверждено сделок: {$confirmed} из ".count($expired).'.');

        return self::SUCCESS;
    }
}
