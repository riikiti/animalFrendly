<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Enums;

/**
 * В этой фазе создаются только записи InApp — Push/Email вне скоупа (нет мобильного клиента
 * для device_token и настоящего SMTP), см. docs/plan/10-integrations.md.
 */
enum NotificationChannel: string
{
    case InApp = 'in_app';
    case Push = 'push';
    case Email = 'email';
}
