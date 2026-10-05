<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit;

use InvalidArgumentException;
use Lex\Notifications\AnonymousNotifiable;
use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationManager;
use Lex\Notifications\Queue\Message\NotificationQueued;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\QueuedTestNotification;
use Lex\Notifications\Tests\Unit\Fixtures\RecordingChannel;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use Lex\Notifications\Tests\Unit\Fixtures\UnnamedChannel;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NotificationManagerTest extends UnitTestCase
{
    private ?MessageBusInterface $bus = null;
    private RecordingChannel $mailChannel;
    private RecordingChannel $databaseChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailChannel = new RecordingChannel(NotificationChannel::CHANNEL_MAIL);
        $this->databaseChannel = new RecordingChannel(NotificationChannel::CHANNEL_DATABASE);
    }

    /**
     * @param iterable<mixed>|null $channels
     */
    private function createManager(?iterable $channels = null): NotificationManager
    {
        return new NotificationManager(
            $this->bus ?? $this->createStub(MessageBusInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $channels ?? [$this->mailChannel, $this->databaseChannel]
        );
    }

    #[Test]
    public function implementsDispatcherInterface(): void
    {
        self::assertInstanceOf(NotificationDispatcherInterface::class, $this->createManager());
    }

    #[Test]
    public function channelsAreResolvedByTheirName(): void
    {
        $manager = $this->createManager();

        self::assertSame($this->mailChannel, $manager->channel(NotificationChannel::CHANNEL_MAIL));
        self::assertSame($this->databaseChannel, $manager->channel(NotificationChannel::CHANNEL_DATABASE));
    }

    #[Test]
    public function channelsWithoutGetNameAreResolvedByClassShortName(): void
    {
        $unnamed = new UnnamedChannel();
        $classShortName = basename(str_replace('\\', '/', $unnamed::class));
        $manager = $this->createManager([$this->mailChannel, $unnamed]);

        self::assertSame($unnamed, $manager->channel($classShortName));
    }

    #[Test]
    public function channelWithoutNameReturnsTheMailChannelAsDefault(): void
    {
        $manager = $this->createManager();

        self::assertSame($this->mailChannel, $manager->channel());
        self::assertSame($this->mailChannel, $manager->channel(null));
        self::assertSame($this->mailChannel, $manager->channel(''));
    }

    #[Test]
    public function unknownChannelThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Notification channel 'sms' not supported.");

        $this->createManager()->channel('sms');
    }

    #[Test]
    public function objectsNotImplementingChannelInterfaceAreIgnored(): void
    {
        $manager = $this->createManager([$this->mailChannel, new stdClass()]);

        $this->expectException(InvalidArgumentException::class);
        $manager->channel(stdClass::class);
    }

    #[Test]
    public function aLaterChannelWithTheSameNameOverridesTheEarlierOne(): void
    {
        $override = new RecordingChannel(NotificationChannel::CHANNEL_MAIL);
        $manager = $this->createManager([$this->mailChannel, $override]);

        self::assertSame($override, $manager->channel(NotificationChannel::CHANNEL_MAIL));
    }

    #[Test]
    public function channelsCanBeProvidedAsGenerator(): void
    {
        $generator = (function () {
            yield $this->mailChannel;
            yield $this->databaseChannel;
        })();

        $manager = $this->createManager($generator);

        self::assertSame($this->databaseChannel, $manager->channel(NotificationChannel::CHANNEL_DATABASE));
    }

    #[Test]
    public function sendNowDeliversToEveryChannelReturnedByVia(): void
    {
        $user = new NotifiableUser();
        $notification = new TestNotification([NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE]);

        $this->createManager()->sendNow($user, $notification);

        self::assertCount(1, $this->mailChannel->sent);
        self::assertCount(1, $this->databaseChannel->sent);
        self::assertSame($user, $this->mailChannel->sent[0]['notifiable']);
        self::assertSame($user, $this->databaseChannel->sent[0]['notifiable']);
    }

    #[Test]
    public function sendNowUsesTheBaseClassDefaultChannels(): void
    {
        $this->createManager()->sendNow(new NotifiableUser(), new TestNotification());

        self::assertCount(1, $this->mailChannel->sent);
        self::assertCount(1, $this->databaseChannel->sent);
    }

    #[Test]
    public function sendNowWithExplicitChannelsOverridesVia(): void
    {
        $notification = new TestNotification([NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE]);

        $this->createManager()->sendNow(new NotifiableUser(), $notification, [NotificationChannel::CHANNEL_DATABASE]);

        self::assertCount(0, $this->mailChannel->sent);
        self::assertCount(1, $this->databaseChannel->sent);
    }

    #[Test]
    public function sendNowWithEmptyChannelArrayFallsBackToVia(): void
    {
        $notification = new TestNotification([NotificationChannel::CHANNEL_MAIL]);

        $this->createManager()->sendNow(new NotifiableUser(), $notification, []);

        self::assertCount(1, $this->mailChannel->sent);
        self::assertCount(0, $this->databaseChannel->sent);
    }

    #[Test]
    public function sendNowDoesNothingWhenViaReturnsNoChannel(): void
    {
        $this->createManager()->sendNow(new NotifiableUser(), new TestNotification([]));

        self::assertCount(0, $this->mailChannel->sent);
        self::assertCount(0, $this->databaseChannel->sent);
    }

    #[Test]
    public function sendNowDeliversToEachNotifiableOfAnArray(): void
    {
        $alice = new NotifiableUser(1, 'alice@example.com');
        $bob = new NotifiableUser(2, 'bob@example.com');

        $this->createManager()->sendNow([$alice, $bob], new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(2, $this->mailChannel->sent);
        self::assertSame($alice, $this->mailChannel->sent[0]['notifiable']);
        self::assertSame($bob, $this->mailChannel->sent[1]['notifiable']);
    }

    #[Test]
    public function eachChannelReceivesItsOwnCopyOfTheNotification(): void
    {
        $notification = new TestNotification([NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE]);

        $this->createManager()->sendNow(new NotifiableUser(), $notification);

        $mailCopy = $this->mailChannel->sent[0]['notification'];
        $databaseCopy = $this->databaseChannel->sent[0]['notification'];

        self::assertInstanceOf(TestNotification::class, $mailCopy);
        self::assertNotSame($notification, $mailCopy);
        self::assertNotSame($notification, $databaseCopy);
        self::assertNotSame($mailCopy, $databaseCopy);
    }

    #[Test]
    public function sendNowToNotifiableThatHasNoRouteForItIsIgnored(): void
    {
        $unsupportedChannelName = 'carrier-pigeon';
        $channel = new RecordingChannel($unsupportedChannelName);

        $this->createManager()->sendNow(new class
        {
            use Notifiable;
            // No function routeNotificationForCarrierPigeon so channel not supported
        }, new TestNotification([$channel->getName()]));

        self::assertCount(0, $channel->sent);
    }

    #[Test]
    public function sendNowBypassesTheMessageBusEvenForQueueableNotifications(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->expects(self::never())->method('dispatch');

        $this->createManager()->sendNow(
            [new NotifiableUser(1), new NotifiableUser(2)],
            new QueuedTestNotification([NotificationChannel::CHANNEL_MAIL])
        );

        self::assertCount(2, $this->mailChannel->sent);
    }

    #[Test]
    public function notifyNowOnAUserBypassesTheMessageBusEvenForQueueableNotifications(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->expects(self::never())->method('dispatch');
        GeneralUtility::addInstance(NotificationDispatcherInterface::class, $this->createManager());

        (new NotifiableUser())->notifyNow(new QueuedTestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(1, $this->mailChannel->sent);
    }

    #[Test]
    public function notifyOnAUserQueuesQueueableNotifications(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static fn(object $message): Envelope => new Envelope($message));
        GeneralUtility::addInstance(NotificationDispatcherInterface::class, $this->createManager());

        (new NotifiableUser())->notify(new QueuedTestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(0, $this->mailChannel->sent);
    }

    #[Test]
    public function sendDeliversNonQueueableNotificationsImmediately(): void
    {
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->expects(self::never())->method('dispatch');

        $this->createManager()->send(new NotifiableUser(), new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(1, $this->mailChannel->sent);
    }

    #[Test]
    public function sendDispatchesQueueableNotificationsOnTheMessageBus(): void
    {
        $user = new NotifiableUser();
        $notification = new QueuedTestNotification([NotificationChannel::CHANNEL_MAIL]);

        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (mixed $message) use ($user, $notification): bool {
                return $message instanceof NotificationQueued
                    && $message->notifiables === [$user]
                    && $message->notification === $notification;
            }))
            ->willReturnCallback(static fn(object $message): Envelope => new Envelope($message));

        $this->createManager()->send($user, $notification);

        self::assertCount(0, $this->mailChannel->sent, 'Queued notifications must not be delivered synchronously');
    }

    #[Test]
    public function sendQueuesArraysOfNotifiablesAsIs(): void
    {
        $notifiables = [new NotifiableUser(1), new NotifiableUser(2)];

        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static fn(mixed $message): bool => $message instanceof NotificationQueued && $message->notifiables === $notifiables))
            ->willReturnCallback(static fn(object $message): Envelope => new Envelope($message));

        $this->createManager()->send($notifiables, new QueuedTestNotification());
    }

    #[Test]
    public function routeReturnsAnonymousNotifiableForASupportedChannel(): void
    {
        $notifiable = $this->createManager()->route(NotificationChannel::CHANNEL_MAIL, 'anonymous@example.com');

        self::assertInstanceOf(AnonymousNotifiable::class, $notifiable);
        self::assertSame(
            'anonymous@example.com',
            $notifiable->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, new TestNotification())
        );
    }

    #[Test]
    public function routeRejectsUnknownChannels(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManager()->route('sms', '+33600000000');
    }

    #[Test]
    public function routeRejectsTheDatabaseChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The database channel does not support on-demand notifications.');

        $this->createManager()->route(NotificationChannel::CHANNEL_DATABASE, 1);
    }

    #[Test]
    public function routesRegistersEveryChannelRoutePair(): void
    {
        $notification = new TestNotification();
        $notifiable = $this->createManager()->routes([
            NotificationChannel::CHANNEL_MAIL => 'anonymous@example.com',
            'slack' => 'https://hooks.slack.com/services/xxx',
        ]);

        self::assertSame('anonymous@example.com', $notifiable->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, $notification));
        self::assertSame('https://hooks.slack.com/services/xxx', $notifiable->routeNotificationFor('slack', $notification));
    }

    #[Test]
    public function routesRejectsTheDatabaseChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManager()->routes([NotificationChannel::CHANNEL_DATABASE => 1]);
    }

    #[Test]
    public function onDemandNotificationsAreDeliveredToTheRoutedChannel(): void
    {
        $manager = $this->createManager();
        $notifiable = $manager->route(NotificationChannel::CHANNEL_MAIL, 'anonymous@example.com');

        $manager->sendNow($notifiable, new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(1, $this->mailChannel->sent);
        self::assertSame($notifiable, $this->mailChannel->sent[0]['notifiable']);
    }

    #[Test]
    public function customChannelsCanBeMocked(): void
    {
        $channel = new class implements ChannelInterface {
            public int $calls = 0;
            public function send(object $notifiable, \Lex\Notifications\Notification $notification): void
            {
                $this->calls++;
            }
            public function getName(): string
            {
                return 'custom';
            }
        };

        $notifiable = new class
        {
            use Notifiable;
            public function routeNotificationForCustom(): bool { return true; }
        };

        $this->createManager([$channel])->sendNow($notifiable, new TestNotification([$channel->getName()]));

        self::assertSame(1, $channel->calls);
    }
}
