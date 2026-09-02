<?php declare(strict_types=1);

namespace Lex\Notifications;

use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException;

abstract class Notification
{
    protected int $level = NotificationLevel::LEVEL_INFO;

    public function getLevel() : int
    {
        return $this->level;
    }

    public function via(object $notifiable): array
    {
        return [NotificationChannel::CHANNEL_DATABASE, NotificationChannel::CHANNEL_MAIL];
    }

    public function toMail(object $notifiable): MailMessage
    {
        throw new NotImplementedMethodException(sprintf("Method 'toMail' must be implemented to use channel 'mail' in the notification class %s.", $this->getType()));
    }

    public function toDatabase(object $notifiable): array
    {
        throw new NotImplementedMethodException(sprintf("Method 'toDatabase' must be implemented to use channel 'database' in the notification class %s.", $this->getType()));
    }

    public function getType(): string
    {
        return static::class;
    }
}