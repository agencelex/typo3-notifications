<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Queue;

use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\Queue\Message\NotificationQueued;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\QueuedTestNotification;
use Lex\NotificationsTest\Channel\AttributeTaggedChannel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * TYPO3 routes every message to the synchronous "default" transport unless configured otherwise,
 * so ShouldQueue notifications are delivered within the same request by the registered handler.
 */
final class QueuedNotificationTest extends AbstractNotificationsFunctionalTestCase
{
    #[Test]
    public function queuedNotificationIsDeliveredByTheMessageHandler(): void
    {
        (new NotifiableUser(uid: 8))->notify(new QueuedTestNotification([NotificationChannel::CHANNEL_DATABASE]));

        $rows = $this->fetchNotificationRows();
        self::assertCount(1, $rows);
        self::assertSame(QueuedTestNotification::class, $rows[0]['type']);
        self::assertSame(8, (int)$rows[0]['notifiable_id']);
    }

    #[Test]
    public function queuedNotificationReachesEveryNotifiableAndChannel(): void
    {
        $this->get(NotificationDispatcherInterface::class)->send(
            [new NotifiableUser(uid: 1), new NotifiableUser(uid: 2)],
            new QueuedTestNotification([NotificationChannel::CHANNEL_DATABASE, AttributeTaggedChannel::NAME])
        );

        self::assertCount(2, $this->fetchNotificationRows());
        self::assertCount(2, $this->get(AttributeTaggedChannel::class)->sent);
    }

    #[Test]
    public function notificationQueuedMessageDispatchedOnTheBusIsHandled(): void
    {
        $this->get(MessageBusInterface::class)->dispatch(
            new NotificationQueued([new NotifiableUser(uid: 3)], new QueuedTestNotification([AttributeTaggedChannel::NAME]))
        );

        self::assertCount(1, $this->get(AttributeTaggedChannel::class)->sent);
    }
}
