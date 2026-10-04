<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit;

use Lex\Notifications\Extension;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationLevel;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Guards the public constants integrators rely on (stored in the database, used in via()).
 * Changing any of these values is a breaking change.
 */
final class ConstantsTest extends UnitTestCase
{
    #[Test]
    public function channelIdentifiersAreStable(): void
    {
        self::assertSame('mail', NotificationChannel::CHANNEL_MAIL);
        self::assertSame('database', NotificationChannel::CHANNEL_DATABASE);
    }

    #[Test]
    public function levelValuesAreStable(): void
    {
        self::assertSame(0, NotificationLevel::LEVEL_INFO);
        self::assertSame(1, NotificationLevel::LEVEL_NOTICE);
        self::assertSame(2, NotificationLevel::LEVEL_WARNING);
        self::assertSame(3, NotificationLevel::LEVEL_ERROR);
        self::assertSame(4, NotificationLevel::LEVEL_CRITICAL);
        self::assertSame(5, NotificationLevel::LEVEL_ALERT);
        self::assertSame(6, NotificationLevel::LEVEL_EMERGENCY);
    }

    #[Test]
    public function everyLevelHasALabel(): void
    {
        self::assertSame(
            [
                0 => 'info',
                1 => 'notice',
                2 => 'warning',
                3 => 'error',
                4 => 'critical',
                5 => 'alert',
                6 => 'emergency',
            ],
            NotificationLevel::SUPPORTED_LEVELS
        );
    }

    #[Test]
    public function extensionKeyVariants(): void
    {
        self::assertSame('lex_notifications', Extension::KEY);
        self::assertSame('LexNotifications', Extension::extensionKeyCamelCase());
        self::assertSame('lexNotifications', Extension::extensionKeyLowerCamelCase());
        self::assertSame('lexnotifications', Extension::extensionKeyLowerCase());
    }
}
