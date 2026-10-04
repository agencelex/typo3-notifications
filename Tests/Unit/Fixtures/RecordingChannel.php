<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Notification;

/**
 * Channel that only records what it has been asked to deliver.
 */
final class RecordingChannel implements ChannelInterface
{
    /**
     * @var list<array{notifiable: object, notification: Notification}>
     */
    public array $sent = [];

    public function __construct(
        private readonly string $name
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $this->sent[] = ['notifiable' => $notifiable, 'notification' => $notification];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
