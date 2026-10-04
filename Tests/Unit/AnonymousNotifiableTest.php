<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit;

use InvalidArgumentException;
use Lex\Notifications\AnonymousNotifiable;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AnonymousNotifiableTest extends UnitTestCase
{
    #[Test]
    public function routeIsFluent(): void
    {
        $notifiable = new AnonymousNotifiable();

        self::assertSame($notifiable, $notifiable->route(NotificationChannel::CHANNEL_MAIL, 'a@example.com'));
    }

    #[Test]
    public function routesAreReturnedPerChannel(): void
    {
        $address = new Address('a@example.com', 'Anonymous');
        $notifiable = (new AnonymousNotifiable())
            ->route(NotificationChannel::CHANNEL_MAIL, $address)
            ->route('slack', '#general');

        self::assertSame($address, $notifiable->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, new TestNotification()));
        self::assertSame('#general', $notifiable->routeNotificationFor('slack', new TestNotification()));
    }

    #[Test]
    public function routingTheSameChannelTwiceKeepsTheLastRoute(): void
    {
        $notifiable = (new AnonymousNotifiable())
            ->route(NotificationChannel::CHANNEL_MAIL, 'first@example.com')
            ->route(NotificationChannel::CHANNEL_MAIL, 'second@example.com');

        self::assertSame('second@example.com', $notifiable->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, new TestNotification()));
    }

    #[Test]
    public function unknownChannelHasNoRoute(): void
    {
        self::assertNull((new AnonymousNotifiable())->routeNotificationFor('sms', new TestNotification()));
    }

    #[Test]
    public function databaseChannelIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AnonymousNotifiable())->route(NotificationChannel::CHANNEL_DATABASE, 1);
    }
}
