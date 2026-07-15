<?php

declare(strict_types=1);

return [
    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 3),
];
