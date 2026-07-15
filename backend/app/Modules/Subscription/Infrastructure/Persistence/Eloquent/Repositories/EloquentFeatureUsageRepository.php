<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Modules\Subscription\Domain\Repositories\FeatureUsageRepositoryInterface;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\FeatureUsage;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\Cache;

final class EloquentFeatureUsageRepository implements FeatureUsageRepositoryInterface
{
    public function tryConsume(Id $userId, FeatureKey $featureKey, string $periodStart, ?int $limit): bool
    {
        $lockKey = "feature_usage:{$userId->toString()}:{$featureKey->value}:{$periodStart}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($userId, $featureKey, $periodStart, $limit): bool {
            $usage = FeatureUsage::query()
                ->where('user_id', $userId->toString())
                ->where('feature_key', $featureKey->value)
                ->where('period_start', $periodStart)
                ->first();

            $usedCount = $usage === null ? 0 : $usage->used_count;

            if ($limit !== null && $usedCount >= $limit) {
                return false;
            }

            FeatureUsage::query()->updateOrCreate(
                ['user_id' => $userId->toString(), 'feature_key' => $featureKey->value, 'period_start' => $periodStart],
                ['used_count' => $usedCount + 1],
            );

            return true;
        });
    }
}
