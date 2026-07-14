<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Jobs;

use App\Modules\Payment\Application\Services\ProcessWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

final class ProcessYookassaWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload) {}

    public function handle(ProcessWebhookService $service): void
    {
        $yookassaPaymentId = $this->payload['object']['id'] ?? 'unknown';

        // Параллельные вебхуки по одному и тому же платежу не должны гоняться — правило
        // из docs/rules/04-payments-escrow.md.
        Cache::lock("yookassa-payment:{$yookassaPaymentId}", 10)->block(5, function () use ($service): void {
            $service->process($this->payload);
        });
    }
}
