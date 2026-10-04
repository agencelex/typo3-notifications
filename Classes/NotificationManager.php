<?php declare(strict_types=1);

namespace Lex\Notifications;

use InvalidArgumentException;
use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Channel\DatabaseChannel;
use Lex\Notifications\Channel\EmailChannel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NotificationManager implements NotificationDispatcherInterface
{

    /**
     * The default channel used to deliver messages.
     */
    protected string $defaultChannel = NotificationChannel::CHANNEL_MAIL;

    protected array $channels = [];

    public function __construct(
        protected readonly MessageBusInterface $bus,
        protected readonly EventDispatcherInterface $eventDispatcher,
        #[AutowireIterator('notifications.channel')]
        iterable $channels
    )
    {
        foreach($channels as $channel) {
            if($channel instanceof ChannelInterface) {
                $name = method_exists($channel, 'getName')? $channel->getName() : get_class($channel);
                $this->channels[$name] = $channel;
            }
        }
    }

    public function send(array|object $notifiables, Notification $notification): void
    {
        (new NotificationSender(
            $this,
            $this->bus,
            $this->eventDispatcher
        ))->send($notifiables, $notification);
    }

    public function sendNow(object|array $notifiables, Notification $notification, ?array $channels = null): void
    {
        (new NotificationSender(
            $this,
            $this->bus,
            $this->eventDispatcher
        ))->sendNow($notifiables, $notification, $channels);
    }

    public function channel(?string $name = null): ChannelInterface
    {
        if($name) {
            return $this->channels[$name] ?? throw new InvalidArgumentException("Notification channel '{$name}' not supported.");
        }

        return $this->channels[$this->defaultChannel];
    }

    public function route(string $channel, mixed $route): AnonymousNotifiable
    {
        $this->channel($channel); // This will throw an InvalidArgumentException if the channel is not supported

        return (new AnonymousNotifiable)->route($channel, $route);
    }

    public function routes(array $channels): AnonymousNotifiable
    {
        $notifiable = new AnonymousNotifiable;

        foreach($channels as $channel => $route) {
            $notifiable->route($channel, $route);
        }

        return $notifiable;
    }
}