<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan as EloquentSubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated access', function (): void {
    $petId = Pet::factory()->create()->id;

    $this->getJson("/api/v1/pets/{$petId}/candidates")->assertUnauthorized();
    $this->postJson("/api/v1/pets/{$petId}/swipes", [])->assertUnauthorized();
});

it('excludes own pets and already-swiped pets from the candidate feed', function (): void {
    $owner = User::factory()->create();
    $myPet = Pet::factory()->create(['owner_id' => $owner->id]);
    $myOtherPet = Pet::factory()->create(['owner_id' => $owner->id]);
    $strangerPet = Pet::factory()->create();
    $alreadySwipedPet = Pet::factory()->create();

    DB::table('swipes')->insert([
        'swiper_pet_id' => $myPet->id,
        'target_pet_id' => $alreadySwipedPet->id,
        'action' => 'dislike',
        'created_at' => now(),
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson("/api/v1/pets/{$myPet->id}/candidates");

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($strangerPet->id)
        ->not->toContain($myPet->id)
        ->not->toContain($myOtherPet->id)
        ->not->toContain($alreadySwipedPet->id);
});

it('creates a match when two pet owners like each other', function (): void {
    $ownerA = User::factory()->create();
    $petA = Pet::factory()->create(['owner_id' => $ownerA->id]);

    $ownerB = User::factory()->create();
    $petB = Pet::factory()->create(['owner_id' => $ownerB->id]);

    Sanctum::actingAs($ownerB);
    $this->postJson("/api/v1/pets/{$petB->id}/swipes", [
        'target_pet_id' => $petA->id,
        'action' => 'like',
    ])->assertCreated()->assertJsonPath('is_match', false);

    Sanctum::actingAs($ownerA);
    $response = $this->postJson("/api/v1/pets/{$petA->id}/swipes", [
        'target_pet_id' => $petB->id,
        'action' => 'like',
    ]);

    $response->assertCreated()->assertJsonPath('is_match', true);

    $matchesResponse = $this->getJson("/api/v1/pets/{$petA->id}/matches");
    $matchesResponse->assertOk()->assertJsonCount(1, 'data');
});

it('rejects swiping with a pet owned by someone else', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $someonesPet = Pet::factory()->create();
    $target = Pet::factory()->create();

    $this->postJson("/api/v1/pets/{$someonesPet->id}/swipes", [
        'target_pet_id' => $target->id,
        'action' => 'like',
    ])->assertForbidden();
});

it('rejects a duplicate swipe on the same target', function (): void {
    $owner = User::factory()->create();
    $myPet = Pet::factory()->create(['owner_id' => $owner->id]);
    $target = Pet::factory()->create();

    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/pets/{$myPet->id}/swipes", [
        'target_pet_id' => $target->id,
        'action' => 'dislike',
    ])->assertCreated();

    $this->postJson("/api/v1/pets/{$myPet->id}/swipes", [
        'target_pet_id' => $target->id,
        'action' => 'like',
    ])->assertUnprocessable();
});

it('blocks liking once the free tariff daily limit is exhausted', function (): void {
    EloquentSubscriptionPlan::query()->create([
        'slug' => 'free',
        'name_ru' => 'Free',
        'price_amount' => 0,
        'currency' => 'RUB',
        'period' => 'month',
        'features' => ['daily_likes' => 2],
        'is_active' => true,
    ]);

    $owner = User::factory()->create();
    $myPet = Pet::factory()->create(['owner_id' => $owner->id]);
    $targets = Pet::factory()->count(3)->create();

    Sanctum::actingAs($owner);

    foreach ($targets->take(2) as $target) {
        $this->postJson("/api/v1/pets/{$myPet->id}/swipes", [
            'target_pet_id' => $target->id,
            'action' => 'like',
        ])->assertCreated();
    }

    $this->postJson("/api/v1/pets/{$myPet->id}/swipes", [
        'target_pet_id' => $targets->last()->id,
        'action' => 'like',
    ])->assertStatus(402)->assertJsonPath('error_code', 'quota_exceeded');
});
