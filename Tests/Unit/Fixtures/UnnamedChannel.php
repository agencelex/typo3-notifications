<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Notification;

/**
 * Channel without getName(): the manager must register it under its class name.
 */
final class UnnamedChannel implements ChannelInterface
{
    public int $sendCount = 0;

    public function send(object $notifiable, Notification $notification): void
    {
        $this->sendCount++;
    }
}
