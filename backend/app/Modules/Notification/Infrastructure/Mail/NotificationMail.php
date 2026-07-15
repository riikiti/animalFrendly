<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Mail;

use App\Modules\Notification\Domain\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class NotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const array SUBJECTS = [
        'new_match' => 'У вас новый мэтч',
        'new_message' => 'Новое сообщение',
        'adoption_approved' => 'Заявка на усыновление одобрена',
        'deal_completed' => 'Сделка завершена',
    ];

    public function __construct(
        public readonly NotificationType $type,
        public readonly string $body,
    ) {}

    public function build(): self
    {
        return $this
            ->subject(self::SUBJECTS[$this->type->value])
            ->view('emails.notification');
    }
}
