<?php declare(strict_types=1);

namespace Lex\NotificationsTest\Channel;

use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Notification;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Registration "Option B" from the documentation: #[AutoconfigureTag].
 */
#[AutoconfigureTag('notifications.channel')]
#[Autoconfigure(public: true)]
final class AttributeTaggedChannel implements ChannelInterface
{
    public const NAME = 'attribute-tagged';

    /**
     * @var list<array{notifiable: object, notification: Notification, route: mixed}>
     */
    public array $sent = [];

    public function send(object $notifiable, Notification $notification): void
    {
        $this->sent[] = [
            'notifiable' => $notifiable,
            'notification' => $notification,
            'route' => $notifiable->routeNotificationFor(self::NAME, $notification),
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
