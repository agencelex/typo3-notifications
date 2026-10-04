<?php declare(strict_types=1);

namespace Lex\Notifications;

use InvalidArgumentException;
use Lex\Notifications\Domain\Model\Ability\Notifiable;

class AnonymousNotifiable
{
    use Notifiable;

    /**
     * All the notification routing information.
     * @var array $routes
     */
    protected array $routes = [];

    /**
     * Add routing information to the target.
     *
     * @param string $channel
     * @param mixed $route
     * @return static
     *
     */
    public function route(string $channel, mixed $route): static
    {
        if($channel === NotificationChannel::CHANNEL_DATABASE) {
            throw new InvalidArgumentException('The database channel does not support on-demand notifications.');
        }

        $this->routes[$channel] = $route;

        return $this;
    }

    /**
     * Get the notification routing information for the given channel.
     *
     * @param string $channel
     * @param Notification $notification
     * @return mixed
     */
    public function routeNotificationFor(string $channel, Notification $notification): mixed
    {
        return $this->routes[$channel] ?? null;
    }
}