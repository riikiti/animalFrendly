<?php

declare(strict_types=1);

namespace App\Modules\Payment\Presentation\Http\Controllers;

use App\Modules\Payment\Infrastructure\Jobs\ProcessYookassaWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

final class YookassaWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        if ((bool) config('yookassa.verify_webhook_ip') && ! $this->isFromYookassa($request)) {
            return response()->json(['message' => 'Источник не подтверждён.'], 403);
        }

        /** @var array<string, mixed>|null $payload */
        $payload = $request->json()->all();

        if (! is_array($payload) || ! isset($payload['event'], $payload['object']['id'])) {
            return response()->json(['message' => 'Некорректный payload.'], 422);
        }

        // Обработка — не синхронно в HTTP-хендлере, см. docs/rules/04-payments-escrow.md.
        ProcessYookassaWebhookJob::dispatch($payload);

        return response()->json(['status' => 'ok']);
    }

    private function isFromYookassa(Request $request): bool
    {
        $ip = $request->ip();

        if ($ip === null) {
            return false;
        }

        /** @var list<string> $ranges */
        $ranges = config('yookassa.webhook_ip_ranges', []);

        return IpUtils::checkIp($ip, $ranges);
    }
}
