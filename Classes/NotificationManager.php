<?php declare(strict_types=1);

namespace Lex\Notifications;

use InvalidArgumentException;
use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Channel\DatabaseChannel;
use Lex\Notifications\Channel\EmailChannel;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NotificationManager implements NotificationDispatcherInterface
{
    public function __construct(
        protected readonly MessageBusInterface $bus,
        protected readonly EventDispatcherInterface $eventDispatcher,
    )
    {}

    /**
     * The default channel used to deliver messages.
     */
    protected string $defaultChannel = NotificationChannel::CHANNEL_MAIL;

    protected array $channels = [
        NotificationChannel::CHANNEL_MAIL => EmailChannel::class,
        NotificationChannel::CHANNEL_DATABASE => DatabaseChannel::class,
    ];

    public function send(array|object $notifiables, Notification $notification): void
    {
        (new NotificationSender(
            $this,
            $this->bus,
            $this->eventDispatcher
        ))->send($notifiables, $notification);
    }

    public function sendNow(object|array $notifiables, Notification $notification, array $channels = null): void
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
     * @param string|null $name The name of the notification channel.
     * @return ChannelInterface The channel instance corresponding to the given name and to the default channel.
     *
     * @throws InvalidArgumentException If the provided channel name is not supported.
     */
    public function channel(?string $name = null): ChannelInterface
    {
        if($name) {
            return isset($this->channels[$name]) ? GeneralUtility::makeInstance($this->channels[$name]) : throw new InvalidArgumentException("Notification channel '{$name}' not supported.");
        }

        return GeneralUtility::makeInstance($this->channels[$this->defaultChannel]);
    }
}