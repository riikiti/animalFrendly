<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\External;

use App\Modules\Identity\Application\Contracts\SmsSenderInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use Psr\Log\LoggerInterface;

/**
 * Отправка «в лог» — реализация по умолчанию, пока не подключён оператор рассылки.
 * Код виден в storage/logs, этого достаточно для разработки и автотестов.
 */
final class LogSmsSender implements SmsSenderInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function send(PhoneNumber $phone, string $text): void
    {
        $this->logger->info('SMS', ['phone' => $phone->value(), 'text' => $text]);
    }
}
