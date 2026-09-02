<?php declare(strict_types=1);

namespace Lex\Notifications\Queue\Message;

use Lex\Notifications\Notification;

final readonly class NotificationQueued
{
    public function __construct(
        public array $notifiables,
        public Notification $notification,
    )
    {}
}