<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit;

use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationLevel;
use Lex\Notifications\Tests\Unit\Fixtures\BareNotification;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NotificationTest extends UnitTestCase
{
    #[Test]
    public function defaultLevelIsInfo(): void
    {
        self::assertSame(NotificationLevel::LEVEL_INFO, (new BareNotification())->getLevel());
    }

    #[Test]
    public function levelCanBeSetBySubclasses(): void
    {
        $notification = new TestNotification(level: NotificationLevel::LEVEL_CRITICAL);

        self::assertSame(NotificationLevel::LEVEL_CRITICAL, $notification->getLevel());
    }

    #[Test]
    public function defaultChannelsAreDatabaseAndMail(): void
    {
        self::assertSame(
            [NotificationChannel::CHANNEL_DATABASE, NotificationChannel::CHANNEL_MAIL],
            (new BareNotification())->via(new NotifiableUser())
        );
    }

    #[Test]
    public function typeIsTheConcreteClassName(): void
    {
        self::assertSame(BareNotification::class, (new BareNotification())->getType());
        self::assertSame(TestNotification::class, (new TestNotification())->getType());
    }

    #[Test]
    public function toMailMustBeImplementedToUseTheMailChannel(): void
    {
        $this->expectException(NotImplementedMethodException::class);
        $this->expectExceptionMessage(BareNotification::class);

        (new BareNotification())->toMail(new NotifiableUser());
    }

    #[Test]
    public function toDatabaseMustBeImplementedToUseTheDatabaseChannel(): void
    {
        $this->expectException(NotImplementedMethodException::class);
        $this->expectExceptionMessage(BareNotification::class);

        (new BareNotification())->toDatabase(new NotifiableUser());
    }
}
