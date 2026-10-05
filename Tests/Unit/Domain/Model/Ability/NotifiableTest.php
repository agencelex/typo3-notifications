<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Domain\Model\Ability;

use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\Tests\Unit\Fixtures\EmailOnlyRecipient;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NotifiableTest extends UnitTestCase
{
    #[Test]
    public function notifyDelegatesToTheDispatcherSend(): void
    {
        $user = new NotifiableUser();
        $notification = new TestNotification();

        $dispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('send')->with($user, $notification);
        $dispatcher->expects(self::never())->method('sendNow');
        GeneralUtility::addInstance(NotificationDispatcherInterface::class, $dispatcher);

        $user->notify($notification);
    }

    #[Test]
    public function notifyNowDelegatesToTheDispatcherSendNowWithChannels(): void
    {
        $user = new NotifiableUser();
        $notification = new TestNotification();

        $dispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('sendNow')->with($user, $notification, [NotificationChannel::CHANNEL_DATABASE]);
        $dispatcher->expects(self::never())->method('send');
        GeneralUtility::addInstance(NotificationDispatcherInterface::class, $dispatcher);

        $user->notifyNow($notification, [NotificationChannel::CHANNEL_DATABASE]);
    }

    #[Test]
    public function notifyNowWithoutChannelsPassesNull(): void
    {
        $user = new NotifiableUser();
        $notification = new TestNotification();

        $dispatcher = $this->createMock(NotificationDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('sendNow')->with($user, $notification, null);
        GeneralUtility::addInstance(NotificationDispatcherInterface::class, $dispatcher);

        $user->notifyNow($notification);
    }

    #[Test]
    public function routeForDatabaseChannelReturnsTrue(): void
    {
        self::assertTrue((new NotifiableUser())->routeNotificationFor(NotificationChannel::CHANNEL_DATABASE, new TestNotification()));
    }

    #[Test]
    public function mailRouteIsResolvedThroughRouteNotificationForMail(): void
    {
        $route = (new NotifiableUser())->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, new TestNotification());

        self::assertInstanceOf(Address::class, $route);
        self::assertSame('jane.doe@example.com', $route->getAddress());
    }

    #[Test]
    public function customChannelRouteIsResolvedByConvention(): void
    {
        $user = new NotifiableUser(slackWebhook: 'https://hooks.slack.com/services/T0/B0/X');

        self::assertSame(
            'https://hooks.slack.com/services/T0/B0/X',
            $user->routeNotificationFor('slack', new TestNotification())
        );
    }

    #[Test]
    public function channelWithoutRouteMethodReturnsNull(): void
    {
        self::assertNull((new EmailOnlyRecipient())->routeNotificationFor('slack', new TestNotification()));
    }
}
