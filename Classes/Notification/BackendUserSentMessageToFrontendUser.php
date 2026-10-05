<?php declare(strict_types=1);

namespace Lex\Notifications\Notification;

use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Lex\Notifications\Domain\Model\Ability\CanSendMailMessage;
use Lex\Notifications\Notification;
use Lex\Notifications\NotificationChannel;

class BackendUserSentMessageToFrontendUser extends Notification implements ShouldQueue
{
    use CanSendMailMessage;

    public function __construct(
        protected readonly string  $subject,
        protected readonly string  $message,
        protected int              $level,
        protected readonly ?string $link,
        protected readonly array $channels = []
    )
    {}

    public function via(object $notifiable): array
    {
        return empty($this->channels) ? [
            NotificationChannel::CHANNEL_MAIL,
            NotificationChannel::CHANNEL_DATABASE
        ] : $this->channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'level' => $this->level,
            'subject' => $this->subject,
            'message' => $this->message,
            'link' => $this->link,
            'creation_datetime' => (new DateTime("now", new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
        ];
    }
}