<?php

declare(strict_types=1);

it('rejects a malformed payload', function (): void {
    config(['yookassa.verify_webhook_ip' => false]);

    $this->postJson('/api/v1/payments/webhooks/yookassa', ['event' => 'payment.succeeded'])
        ->assertUnprocessable();
});

it('rejects requests from an IP outside the configured ЮKassa ranges', function (): void {
    config([
        'yookassa.verify_webhook_ip' => true,
        'yookassa.webhook_ip_ranges' => ['203.0.113.0/24'],
    ]);

    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => ['id' => 'yk-123'],
    ])->assertForbidden();
});

it('accepts requests from an allowed IP and returns ok even for an unknown payment', function (): void {
    config([
        'yookassa.verify_webhook_ip' => true,
        'yookassa.webhook_ip_ranges' => ['127.0.0.1/32'],
    ]);

    $this->postJson('/api/v1/payments/webhooks/yookassa', [
        'event' => 'payment.succeeded',
        'object' => ['id' => 'yk-unknown'],
    ])->assertOk()->assertJsonPath('status', 'ok');
});
