<?php declare(strict_types=1);

namespace Lex\Notifications;

interface NotificationDispatcherInterface
{
    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param array|object $notifiables
     * @param Notification $notification
     * @return void
     */
    public function send(array|object $notifiables, Notification $notification): void;

    /**
     * Send the given notification immediately.
     *
     * @param array|object $notifiables
     * @param Notification $notification
     * @param array|null $channels
     * @return void
     */
    public function sendNow(array|object $notifiables, Notification $notification, array $channels = null): void;
}