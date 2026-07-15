<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects access to the admin panel for a regular user', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/summary')->assertForbidden();
    $this->getJson('/api/v1/admin/audit-log')->assertForbidden();
});

it('returns a summary of pending reports for staff', function (): void {
    $reporter = User::factory()->create();
    Sanctum::actingAs($reporter);
    $this->postJson('/api/v1/reports', ['target_type' => 'pet', 'target_id' => 'x', 'reason' => 'spam'])
        ->assertCreated();

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);

    $response = $this->getJson('/api/v1/admin/summary')->assertOk();
    expect($response->json('data.pending_reports'))->toBe(1)
        ->and($response->json('data.pending_shelter_verifications'))->toBe(0)
        ->and($response->json('data.open_disputes'))->toBe(0);
});

it('lists recent audit log entries after a moderator action', function (): void {
    $reporter = User::factory()->create();
    Sanctum::actingAs($reporter);
    $reportId = $this->postJson('/api/v1/reports', ['target_type' => 'pet', 'target_id' => 'x', 'reason' => 'spam'])
        ->json('data.id');

    $moderator = User::factory()->create(['account_type' => 'moderator']);
    Sanctum::actingAs($moderator);
    $this->postJson("/api/v1/moderation/reports/{$reportId}/review")->assertOk();

    $response = $this->getJson('/api/v1/admin/audit-log')->assertOk();
    expect($response->json('data.0.action'))->toBe('report.reviewed')
        ->and($response->json('data.0.actor_id'))->toBe($moderator->id);
});
