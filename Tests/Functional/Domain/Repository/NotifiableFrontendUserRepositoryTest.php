<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Domain\Repository;

use Lex\Notifications\Domain\Model\NotifiableFrontendUser;
use Lex\Notifications\Domain\Repository\NotifiableFrontendUserRepository;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use Lex\NotificationsTest\Mail\CapturingTransport;
use PHPUnit\Framework\Attributes\Test;

final class NotifiableFrontendUserRepositoryTest extends AbstractNotificationsFunctionalTestCase
{
    private NotifiableFrontendUserRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/fe_users.csv');
        $this->subject = $this->get(NotifiableFrontendUserRepository::class);
    }

    #[Test]
    public function findByUidsReturnsUsersRegardlessOfTheirStoragePage(): void
    {
        $result = $this->subject->findByUids([1, 2, 4])->toArray();

        $uids = array_map(static fn(NotifiableFrontendUser $user): ?int => $user->getUid(), $result);
        sort($uids);
        self::assertSame([1, 2, 4], $uids);
    }

    #[Test]
    public function findByUidsSkipsDeletedUsers(): void
    {
        self::assertCount(0, $this->subject->findByUids([3]));
    }

    #[Test]
    public function frontendUserFieldsAreMapped(): void
    {
        $user = $this->subject->findByUids([1])->getFirst();

        self::assertInstanceOf(NotifiableFrontendUser::class, $user);
        self::assertSame('jane.doe@example.com', $user->getEmail());
        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
    }

    #[Test]
    public function frontendUsersCanBeNotifiedOnAllBuiltInChannels(): void
    {
        $users = $this->subject->findByUids([1, 4])->toArray();

        $this->get(NotificationDispatcherInterface::class)->sendNow($users, new TestNotification([
            NotificationChannel::CHANNEL_MAIL,
            NotificationChannel::CHANNEL_DATABASE,
        ]));

        $recipients = array_map(
            static fn($sent): string => $sent->getOriginalMessage()->getTo()[0]->toString(),
            CapturingTransport::$messages
        );
        sort($recipients);
        self::assertSame(['"Jane Doe" <jane.doe@example.com>', 'anon@example.com'], $recipients);

        $notifiableIds = array_map(static fn(array $row): int => (int)$row['notifiable_id'], $this->fetchNotificationRows());
        sort($notifiableIds);
        self::assertSame([1, 4], $notifiableIds);
    }
}
