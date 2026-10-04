<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Queue;

use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\Queue\Handler\SendQueuedNotificationNow;
use Lex\Notifications\Queue\Message\NotificationQueued;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\QueuedTestNotification;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SendQueuedNotificationNowTest extends UnitTestCase
{
    #[Test]
    public function messageCarriesNotifiablesAndNotification(): void
    {
        $notifiables = [new NotifiableUser(1), new NotifiableUser(2)];
        $notification = new QueuedTestNotification();

        $message = new NotificationQueued($notifiables, $notification);

        self::assertSame($notifiables, $message->notifiables);
        self::assertSame($notification, $message->notification);
    }

    #[Test]
    public function messageIsSerializableForAsyncTransports(): void
    {
        $message = new NotificationQueued([new NotifiableUser(3)], new QueuedTestNotification(['mail'], ['a' => 1]));

        $restored = unserialize(serialize($message));

        self::assertInstanceOf(NotificationQueued::class, $restored);
        self::assertSame(3, $restored->notifiables[0]->getUid());
        self::assertSame(['mail'], $restored->notification->via($restored->notifiables[0]));
    }

    #[Test]
    public function handlerSendsTheQueuedNotificationImmediately(): void
    {
        $notifiables = [new NotifiableUser()];
        $notification = new QueuedTestNotification();

        $dispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('sendNow')->with($notifiables, $notification);
        $dispatcher->expects(self::never())->method('send');

        (new SendQueuedNotificationNow($dispatcher))(new NotificationQueued($notifiables, $notification));
    }
}
