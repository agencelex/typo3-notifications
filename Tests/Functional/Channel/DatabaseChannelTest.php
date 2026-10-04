<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Channel;

use Lex\Notifications\Domain\Model\NotifiableFrontendUser;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationLevel;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;

final class DatabaseChannelTest extends AbstractNotificationsFunctionalTestCase
{
    #[Test]
    public function notifyNowStoresARowInTheNotificationTable(): void
    {
        $user = new NotifiableUser(uid: 12);
        $notification = new TestNotification(
            [NotificationChannel::CHANNEL_DATABASE],
            ['subject' => 'Welcome', 'tags' => ['a', 'b']],
            level: NotificationLevel::LEVEL_ERROR
        );

        $user->notifyNow($notification);

        $rows = $this->fetchNotificationRows();
        self::assertCount(1, $rows);
        $row = $rows[0];
        self::assertSame(TestNotification::class, $row['type']);
        self::assertSame(12, (int)$row['notifiable_id']);
        self::assertSame(NotifiableUser::class, $row['notifiable_type']);
        self::assertSame(NotificationLevel::LEVEL_ERROR, (int)$row['level']);
        self::assertSame(['subject' => 'Welcome', 'tags' => ['a', 'b']], json_decode($row['data'], true));
        self::assertSame(0, (int)$row['read_at']);
        self::assertSame(-1, (int)$row['sys_language_uid']);
        self::assertSame(0, (int)$row['deleted']);
        self::assertGreaterThan(0, (int)$row['crdate']);
    }

    #[Test]
    public function oneRowIsStoredPerNotifiable(): void
    {
        $this->get(NotificationDispatcherInterface::class)->sendNow(
            [new NotifiableUser(uid: 1), new NotifiableUser(uid: 2), new NotifiableUser(uid: 3)],
            new TestNotification([NotificationChannel::CHANNEL_DATABASE])
        );

        self::assertSame([1, 2, 3], array_map(static fn(array $row): int => (int)$row['notifiable_id'], $this->fetchNotificationRows()));
    }

    #[Test]
    public function extbaseEntitiesCanBeNotified(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/fe_users.csv');
        $user = new NotifiableFrontendUser();
        $user->_setProperty('uid', 1);

        $user->notifyNow(new TestNotification([NotificationChannel::CHANNEL_DATABASE]));

        $rows = $this->fetchNotificationRows();
        self::assertCount(1, $rows);
        self::assertSame(1, (int)$rows[0]['notifiable_id']);
        self::assertSame(NotifiableFrontendUser::class, $rows[0]['notifiable_type']);
    }

    #[Test]
    public function nothingIsStoredWhenTheDatabaseChannelIsNotRequested(): void
    {
        (new NotifiableUser())->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertSame([], $this->fetchNotificationRows());
    }
}
