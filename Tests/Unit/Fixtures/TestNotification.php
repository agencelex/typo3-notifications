<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Notification;
use Lex\Notifications\NotificationLevel;
use TYPO3\CMS\Core\Mail\MailMessage;

class TestNotification extends Notification
{
    /**
     * @param list<string>|null $channels Channels returned by via(), null keeps the parent default
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly ?array $channels = null,
        private readonly array $payload = ['foo' => 'bar'],
        private readonly ?string $explicitRecipient = null,
        int $level = NotificationLevel::LEVEL_INFO,
    ) {
        $this->level = $level;
    }

    public function via(object $notifiable): array
    {
        return $this->channels ?? parent::via($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject('Test notification')
            ->text('Hello');

        if ($this->explicitRecipient !== null) {
            $mail->to($this->explicitRecipient);
        }

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
