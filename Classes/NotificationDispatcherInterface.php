<?php declare(strict_types=1);

namespace Lex\Notifications;

use InvalidArgumentException;
use Lex\Notifications\Channel\ChannelInterface;

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
    public function sendNow(array|object $notifiables, Notification $notification, ?array $channels = null): void;

    /**
     * Get the notification channel instance based on the provided name.
     * If name is null or not supplied, an instance of the default channel is returned.
     *
     * @param string|null $name The name or the class name of the notification channel.
     * @return ChannelInterface The channel instance corresponding to the given name and to the default channel.
     *
     * @throws InvalidArgumentException If the provided channel name is not supported.
     */
    public function channel(?string $name = null): ChannelInterface;

    /**
     *  Begin sending a notification to an anonymous notifiable
     *
     * @param string $channel One of the channels injected in the constructor
     * @param mixed $route
     * @return AnonymousNotifiable
     * @throws InvalidArgumentException
     */
    public function route(string $channel, mixed $route): AnonymousNotifiable;

    /**
     * Begin sending a notification to an anonymous notifiable on the given channels.
     *
     * @param array $channels array of channel/route pairs
     * @return AnonymousNotifiable
     * @throws InvalidArgumentException
     */
    public function routes(array $channels): AnonymousNotifiable;
}