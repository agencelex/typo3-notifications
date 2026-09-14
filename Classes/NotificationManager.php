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

    /**
     * Get the notification channel instance based on the provided name.
     * If name is null or not supplied, an instance of the default channel is returned.
     *
     * @param string|null $name The name or the class name of the notification channel.
     * @return ChannelInterface The channel instance corresponding to the given name and to the default channel.
     *
     * @throws InvalidArgumentException If the provided channel name is not supported.
     */
    public function channel(?string $name = null): ChannelInterface
    {
        if($name) {
            return $this->channels[$name] ?? throw new InvalidArgumentException("Notification channel '{$name}' not supported.");
        }

        return $this->channels[$this->defaultChannel];
    }
}