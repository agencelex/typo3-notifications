<?php declare(strict_types=1);

namespace Lex\Notifications\Queue\Handler;

use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\Queue\Message\NotificationQueued;

final readonly class SendQueuedNotificationNow
{
    public function __construct(
        private NotificationDispatcherInterface $dispatcher
    )
    {}

    public function __invoke(NotificationQueued $message): void
    {
        $this->dispatcher->sendNow(
            $message->notifiables,
            $message->notification
        );
    }
}