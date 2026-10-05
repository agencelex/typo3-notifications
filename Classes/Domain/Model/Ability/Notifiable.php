<?php declare(strict_types=1);

namespace Lex\Notifications\Domain\Model\Ability;

use Lex\Notifications\Notification;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface as Dispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait Notifiable
{
    public function notify(Notification $notification): void
    {
        GeneralUtility::makeInstance(Dispatcher::class)
            ->send($this, $notification);
    }

    public function notifyNow(Notification $notification, ?array $channels = null): void
    {
        GeneralUtility::makeInstance(Dispatcher::class)
            ->sendNow($this, $notification, $channels);
    }

    public function routeNotificationFor(string $channel, Notification $notification): mixed
    {
        return match ($channel) {
            NotificationChannel::CHANNEL_DATABASE => true, // Database notification has no route, let go
            default => (function() use($channel, $notification) {
                $method = 'routeNotificationFor' . GeneralUtility::underscoredToUpperCamelCase(str_replace(['-', ' '], '_', $channel)); // Example: routeNotificationForMail or routeNotificationForMyCustomChannel
                return method_exists($this, $method) ? $this->{$method}($notification) : null;
            })(),
        };
    }
}