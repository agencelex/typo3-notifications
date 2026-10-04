<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Domain\Repository;

use DateTime;
use Lex\Notifications\Domain\Model\DatabaseNotification;
use Lex\Notifications\Domain\Repository\DatabaseNotificationRepository;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;

final class DatabaseNotificationRepositoryTest extends AbstractNotificationsFunctionalTestCase
{
    private DatabaseNotificationRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/notifications.csv');
        $this->subject = $this->get(DatabaseNotificationRepository::class);
    }

    #[Test]
    public function findByNotifiableReturnsOnlyNotificationsOfThatNotifiableNewestFirst(): void
    {
        $result = $this->subject->findByNotifiable(1)->toArray();

        self::assertContainsOnlyInstancesOf(DatabaseNotification::class, $result);
        self::assertSame([2, 3, 1], array_map(static fn(DatabaseNotification $n): ?int => $n->getUid(), $result));
    }

    #[Test]
    public function findByNotifiableIgnoresTheStoragePage(): void
    {
        $result = $this->subject->findByNotifiable(2)->toArray();

        self::assertCount(1, $result);
        self::assertSame(4, $result[0]->getUid());
    }

    #[Test]
    public function findByNotifiableReturnsNothingForUnknownNotifiable(): void
    {
        self::assertCount(0, $this->subject->findByNotifiable(999));
    }

    #[Test]
    public function rowsAreMappedToTheModel(): void
    {
        $notification = $this->subject->findByNotifiable(1)->toArray()[1]; // uid 3

        self::assertSame('Vendor\Notification\A', $notification->getType());
        self::assertSame(1, $notification->getNotifiableId());
        self::assertSame(1, $notification->getLevel());
        self::assertSame(['subject' => 'middle'], $notification->getDataAsArray());
        self::assertInstanceOf(DateTime::class, $notification->getReadAt());
        self::assertSame(1700000200, $notification->getReadAt()->getTimestamp());
        self::assertInstanceOf(DateTime::class, $notification->getCreatedAt());
        self::assertSame(1700000100, $notification->getCreatedAt()->getTimestamp());
        self::assertIsString($notification->getDiffCreatedAtForHumans());
    }

    #[Test]
    public function unreadNotificationsHaveNoReadDate(): void
    {
        $notification = $this->subject->findByNotifiable(1)->toArray()[0]; // uid 2

        self::assertNull($notification->getReadAt());
    }

    #[Test]
    public function markAllAsReadForNotifiableOnlyAffectsThatNotifiable(): void
    {
        $before = time();

        $this->subject->markAllAsReadForNotifiable(1);

        $readAt = [];
        foreach ($this->fetchNotificationRows() as $row) {
            $readAt[(int)$row['uid']] = (int)$row['read_at'];
        }
        self::assertGreaterThanOrEqual($before, $readAt[1]);
        self::assertGreaterThanOrEqual($before, $readAt[2]);
        self::assertGreaterThanOrEqual($before, $readAt[3]);
        self::assertSame(0, $readAt[4]);
    }

    #[Test]
    public function removeAllForNotifiableOnlyDeletesThatNotifiable(): void
    {
        $this->subject->removeAllForNotifiable(1);

        $rows = $this->fetchNotificationRows();
        self::assertCount(1, $rows);
        self::assertSame(4, (int)$rows[0]['uid']);
    }

    #[Test]
    public function notificationStoredByTheDatabaseChannelCanBeReadBack(): void
    {
        (new NotifiableUser(uid: 77))->notifyNow(new TestNotification([NotificationChannel::CHANNEL_DATABASE], ['subject' => 'round trip']));

        $result = $this->subject->findByNotifiable(77)->toArray();

        self::assertCount(1, $result);
        self::assertSame(TestNotification::class, $result[0]->getType());
        self::assertSame(NotifiableUser::class, $result[0]->getNotifiableType());
        self::assertSame(['subject' => 'round trip'], $result[0]->getDataAsArray());
        self::assertNull($result[0]->getReadAt());
    }
}
