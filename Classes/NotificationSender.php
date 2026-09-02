<?php declare(strict_types=1);

namespace Lex\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Lex\Notifications\Queue\Message\NotificationQueued;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class NotificationSender
{
    public function __construct(
        protected NotificationManager      $manager,
        protected MessageBusInterface      $bus,
        protected EventDispatcherInterface $eventDispatcher
    )
    {}

    public function send(array|object $notifiables, Notification $notification): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueue) {
            $this->queueNotification($notifiables, $notification);
            return;
        }

        $this->sendNow($notifiables, $notification);
    }

    public function sendNow(array|object $notifiables, Notification $notification, array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {

            $viaChannels = $channels ?: $notification->via($notifiable);

            if (!empty($viaChannels)) {
                foreach ($viaChannels as $channel) {
                    $this->sendToNotifiable($notifiable, clone $original, $channel);
                }
            }
        }
    }

    protected function sendToNotifiable(object $notifiable, Notification $notification, $channel): void
    {
        $this->manager->channel($channel)->send($notifiable, $notification);

        //$this->eventDispatcher->dispatch(new NotificationSent($notifiable, $notification, $channel));
    }

    protected function queueNotification(array $notifiables, Notification|ShouldQueue $notification): void
    {
        $this->bus->dispatch(
            new NotificationQueued($notifiables, $notification)
        );
    }

    protected function formatNotifiables(array|object $notifiables): array
    {
        return !is_array($notifiables) ? [$notifiables] : $notifiables;
    }
}