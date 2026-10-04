<?php declare(strict_types=1);

namespace Lex\NotificationsTest\Channel;

use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Notification;

/**
 * Tagged in Configuration/Services.yaml, without getName(): registered under its class name.
 */
final class YamlTaggedChannel implements ChannelInterface
{
    /**
     * @var list<array{notifiable: object, notification: Notification}>
     */
    public array $sent = [];

    public function send(object $notifiable, Notification $notification): void
    {
        $this->sent[] = ['notifiable' => $notifiable, 'notification' => $notification];
    }
}
