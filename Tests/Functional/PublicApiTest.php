<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional;

use InvalidArgumentException;
use Lex\Notifications\AnonymousNotifiable;
use Lex\Notifications\Channel\DatabaseChannel;
use Lex\Notifications\Channel\EmailChannel;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationManager;
use Lex\Notifications\Tests\Unit\Fixtures\EmailOnlyRecipient;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\QueuedTestNotification;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use Lex\NotificationsTest\Channel\AttributeTaggedChannel;
use Lex\NotificationsTest\Mail\CapturingTransport;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\SentMessage;

/**
 * End-to-end tests of the documented ways to send a notification, against a real TYPO3 instance
 * (DI container, mailer, database, message bus).
 */
final class PublicApiTest extends AbstractNotificationsFunctionalTestCase
{
    private const MAIL_AND_DATABASE = [NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE];

    private NotificationManager $notificationDispatcher;
    private NotifiableUser $userA;
    private NotifiableUser $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationDispatcher = $this->get(NotificationDispatcherInterface::class);
        $this->userA = new NotifiableUser(1, 'user.a@example.com', 'User', 'A');
        $this->userB = new NotifiableUser(2, 'user.b@example.com', 'User', 'B');
    }

    /**
     * @return list<string>
     */
    private function mailRecipients(): array
    {
        return array_map(
            static fn(SentMessage $sent): string => $sent->getOriginalMessage()->getTo()[0]->getAddress(),
            CapturingTransport::$messages
        );
    }

    /**
     * @return list<int>
     */
    private function storedNotifiableIds(): array
    {
        return array_map(static fn(array $row): int => (int)$row['notifiable_id'], $this->fetchNotificationRows());
    }

    private function customChannel(): AttributeTaggedChannel
    {
        return $this->get(AttributeTaggedChannel::class);
    }

    // $user->notify(...)

    #[Test]
    public function userNotifyDeliversToEveryChannelOfVia(): void
    {
        $this->userA->notify(new TestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com'], $this->mailRecipients());
        self::assertSame([1], $this->storedNotifiableIds());
    }

    #[Test]
    public function userNotifyWithAQueuedNotificationIsDeliveredByTheQueueHandler(): void
    {
        $this->userA->notify(new QueuedTestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com'], $this->mailRecipients());
        self::assertSame([1], $this->storedNotifiableIds());
    }

    #[Test]
    public function userNotifyUsesTheMailRouteOfARecipientWithoutName(): void
    {
        (new EmailOnlyRecipient('contact@example.com'))->notify(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(1, CapturingTransport::$messages);
        $to = CapturingTransport::$messages[0]->getOriginalMessage()->getTo()[0];
        self::assertSame('contact@example.com', $to->getAddress());
        self::assertSame('', $to->getName());
    }

    // $user->notifyNow(...)

    #[Test]
    public function userNotifyNowDeliversToEveryChannelOfVia(): void
    {
        $this->userA->notifyNow(new TestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com'], $this->mailRecipients());
        self::assertSame([1], $this->storedNotifiableIds());
    }

    #[Test]
    public function userNotifyNowDeliversQueuedNotificationsToo(): void
    {
        $this->userA->notifyNow(new QueuedTestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com'], $this->mailRecipients());
        self::assertSame([1], $this->storedNotifiableIds());
    }

    #[Test]
    public function userNotifyNowWithChannelsOverridesVia(): void
    {
        $this->userA->notifyNow(new TestNotification(self::MAIL_AND_DATABASE), [NotificationChannel::CHANNEL_DATABASE]);

        self::assertSame([], $this->mailRecipients());
        self::assertSame([1], $this->storedNotifiableIds());
    }

    // $notificationDispatcher->send([$userA, $userB], ...)

    #[Test]
    public function dispatcherSendDeliversToEveryUser(): void
    {
        $this->notificationDispatcher->send([$this->userA, $this->userB], new TestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com', 'user.b@example.com'], $this->mailRecipients());
        self::assertSame([1, 2], $this->storedNotifiableIds());
    }

    #[Test]
    public function dispatcherSendWithAQueuedNotificationDeliversToEveryUser(): void
    {
        $this->notificationDispatcher->send([$this->userA, $this->userB], new QueuedTestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com', 'user.b@example.com'], $this->mailRecipients());
        self::assertSame([1, 2], $this->storedNotifiableIds());
    }

    #[Test]
    public function dispatcherSendAcceptsDifferentKindsOfNotifiables(): void
    {
        $this->notificationDispatcher->send(
            [$this->userA, new EmailOnlyRecipient('contact@example.com')],
            new TestNotification([NotificationChannel::CHANNEL_MAIL])
        );

        self::assertSame(['user.a@example.com', 'contact@example.com'], $this->mailRecipients());
    }

    #[Test]
    public function dispatcherSendAcceptsASingleUser(): void
    {
        $this->notificationDispatcher->send($this->userA, new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertSame(['user.a@example.com'], $this->mailRecipients());
    }

    // $notificationDispatcher->sendNow([$userA, $userB], ...)

    #[Test]
    public function dispatcherSendNowDeliversToEveryUser(): void
    {
        $this->notificationDispatcher->sendNow([$this->userA, $this->userB], new TestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com', 'user.b@example.com'], $this->mailRecipients());
        self::assertSame([1, 2], $this->storedNotifiableIds());
    }

    #[Test]
    public function dispatcherSendNowDeliversQueuedNotificationsToEveryUser(): void
    {
        $this->notificationDispatcher->sendNow([$this->userA, $this->userB], new QueuedTestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com', 'user.b@example.com'], $this->mailRecipients());
        self::assertSame([1, 2], $this->storedNotifiableIds());
    }

    #[Test]
    public function dispatcherSendNowWithChannelsOverridesViaForEveryUser(): void
    {
        $this->notificationDispatcher->sendNow(
            [$this->userA, $this->userB],
            new TestNotification(self::MAIL_AND_DATABASE),
            [NotificationChannel::CHANNEL_MAIL]
        );

        self::assertSame(['user.a@example.com', 'user.b@example.com'], $this->mailRecipients());
        self::assertSame([], $this->storedNotifiableIds());
    }

    // $notificationDispatcher->channel(...)->send($user, ...)

    #[Test]
    public function dispatcherChannelDatabaseSendStoresTheNotificationAndIgnoresVia(): void
    {
        $channel = $this->notificationDispatcher->channel(NotificationChannel::CHANNEL_DATABASE);
        self::assertInstanceOf(DatabaseChannel::class, $channel);

        $channel->send($this->userA, new TestNotification([NotificationChannel::CHANNEL_MAIL], ['subject' => 'direct']));

        $rows = $this->fetchNotificationRows();
        self::assertCount(1, $rows);
        self::assertSame(1, (int)$rows[0]['notifiable_id']);
        self::assertSame(['subject' => 'direct'], json_decode($rows[0]['data'], true));
        self::assertSame([], $this->mailRecipients());
    }

    #[Test]
    public function dispatcherChannelDatabaseSendIgnoresShouldQueue(): void
    {
        $this->notificationDispatcher
            ->channel(NotificationChannel::CHANNEL_DATABASE)
            ->send($this->userA, new QueuedTestNotification());

        self::assertSame([1], $this->storedNotifiableIds());
    }

    #[Test]
    public function dispatcherChannelMailSendOnlySendsAnEmail(): void
    {
        $channel = $this->notificationDispatcher->channel(NotificationChannel::CHANNEL_MAIL);
        self::assertInstanceOf(EmailChannel::class, $channel);

        $channel->send($this->userA, new TestNotification(self::MAIL_AND_DATABASE));

        self::assertSame(['user.a@example.com'], $this->mailRecipients());
        self::assertSame([], $this->storedNotifiableIds());
    }

    #[Test]
    public function dispatcherChannelWithAnUnknownNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->notificationDispatcher->channel('unknown');
    }

    // $notificationDispatcher->route(...)->route(...)->notify(...)

    #[Test]
    public function chainedRoutesNotifyDeliversToEveryRoutedChannel(): void
    {
        $this->notificationDispatcher
            ->route(NotificationChannel::CHANNEL_MAIL, 'guest@example.com')
            ->route(AttributeTaggedChannel::NAME, 'room-42')
            ->notify(new TestNotification([NotificationChannel::CHANNEL_MAIL, AttributeTaggedChannel::NAME]));

        self::assertSame(['guest@example.com'], $this->mailRecipients());
        self::assertCount(1, $this->customChannel()->sent);
        self::assertSame('room-42', $this->customChannel()->sent[0]['route']);
        self::assertInstanceOf(AnonymousNotifiable::class, $this->customChannel()->sent[0]['notifiable']);
        self::assertSame([], $this->storedNotifiableIds());
    }

    #[Test]
    public function chainedRoutesNotifyWithAQueuedNotificationIsDelivered(): void
    {
        $this->notificationDispatcher
            ->route(NotificationChannel::CHANNEL_MAIL, 'guest@example.com')
            ->route(AttributeTaggedChannel::NAME, 'room-42')
            ->notify(new QueuedTestNotification([NotificationChannel::CHANNEL_MAIL, AttributeTaggedChannel::NAME]));

        self::assertSame(['guest@example.com'], $this->mailRecipients());
        self::assertSame('room-42', $this->customChannel()->sent[0]['route']);
    }

    #[Test]
    public function chainedRoutesOnlyDeliverToTheChannelsOfVia(): void
    {
        $this->notificationDispatcher
            ->route(NotificationChannel::CHANNEL_MAIL, 'guest@example.com')
            ->route(AttributeTaggedChannel::NAME, 'room-42')
            ->notify(new TestNotification([AttributeTaggedChannel::NAME]));

        self::assertSame([], $this->mailRecipients());
        self::assertCount(1, $this->customChannel()->sent);
    }

    #[Test]
    public function chainedRoutesRejectTheDatabaseChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->notificationDispatcher
            ->route(NotificationChannel::CHANNEL_MAIL, 'guest@example.com')
            ->route(NotificationChannel::CHANNEL_DATABASE, 1);
    }

    // $notificationDispatcher->routes(...)->notify(...)

    #[Test]
    public function routesNotifyDeliversToEveryRoutedChannel(): void
    {
        $this->notificationDispatcher
            ->routes([
                NotificationChannel::CHANNEL_MAIL => 'guest@example.com',
                AttributeTaggedChannel::NAME => 'room-42',
            ])
            ->notify(new TestNotification([NotificationChannel::CHANNEL_MAIL, AttributeTaggedChannel::NAME]));

        self::assertSame(['guest@example.com'], $this->mailRecipients());
        self::assertSame('room-42', $this->customChannel()->sent[0]['route']);
        self::assertSame([], $this->storedNotifiableIds());
    }

    #[Test]
    public function routesNotifyWithAQueuedNotificationIsDelivered(): void
    {
        $this->notificationDispatcher
            ->routes([
                NotificationChannel::CHANNEL_MAIL => 'guest@example.com',
                AttributeTaggedChannel::NAME => 'room-42',
            ])
            ->notify(new QueuedTestNotification([NotificationChannel::CHANNEL_MAIL, AttributeTaggedChannel::NAME]));

        self::assertSame(['guest@example.com'], $this->mailRecipients());
        self::assertSame('room-42', $this->customChannel()->sent[0]['route']);
    }

    #[Test]
    public function routesRejectTheDatabaseChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->notificationDispatcher->routes([
            NotificationChannel::CHANNEL_MAIL => 'guest@example.com',
            NotificationChannel::CHANNEL_DATABASE => 1,
        ]);
    }
}
