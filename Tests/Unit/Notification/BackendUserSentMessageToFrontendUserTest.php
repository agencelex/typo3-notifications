<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Notification;

use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Lex\Notifications\Notification\BackendUserSentMessageToFrontendUser;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationLevel;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendUserSentMessageToFrontendUserTest extends UnitTestCase
{
    #[Test]
    public function isQueued(): void
    {
        self::assertInstanceOf(
            ShouldQueue::class,
            new BackendUserSentMessageToFrontendUser('Subject', 'Body', NotificationLevel::LEVEL_INFO, null)
        );
    }

    #[Test]
    public function viaDefaultsToMailAndDatabase(): void
    {
        $notification = new BackendUserSentMessageToFrontendUser('Subject', 'Body', NotificationLevel::LEVEL_INFO, null);

        self::assertSame(
            [NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE],
            $notification->via(new NotifiableUser())
        );
    }

    #[Test]
    public function viaReturnsTheConfiguredChannels(): void
    {
        $notification = new BackendUserSentMessageToFrontendUser('Subject', 'Body', NotificationLevel::LEVEL_INFO, null, [NotificationChannel::CHANNEL_DATABASE]);

        self::assertSame([NotificationChannel::CHANNEL_DATABASE], $notification->via(new NotifiableUser()));
    }

    #[Test]
    public function levelIsExposed(): void
    {
        $notification = new BackendUserSentMessageToFrontendUser('Subject', 'Body', NotificationLevel::LEVEL_ALERT, null);

        self::assertSame(NotificationLevel::LEVEL_ALERT, $notification->getLevel());
    }

    #[Test]
    public function toDatabaseContainsTheMessagePayload(): void
    {
        $notification = new BackendUserSentMessageToFrontendUser('Subject', 'Body', NotificationLevel::LEVEL_WARNING, 't3://page?uid=1');

        $data = $notification->toDatabase(new NotifiableUser());

        self::assertSame(NotificationLevel::LEVEL_WARNING, $data['level']);
        self::assertSame('Subject', $data['subject']);
        self::assertSame('Body', $data['message']);
        self::assertSame('t3://page?uid=1', $data['link']);
        self::assertInstanceOf(DateTime::class, DateTime::createFromFormat('Y-m-d H:i:s', $data['creation_datetime']));
    }
}
