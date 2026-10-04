<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Notification;

use Lex\Notifications\Domain\Model\NotifiableFrontendUser;
use Lex\Notifications\Domain\Repository\NotifiableFrontendUserRepository;
use Lex\Notifications\Notification\BackendUserSentMessageToFrontendUser;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationLevel;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\NotificationsTest\Mail\CapturingTransport;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;

/**
 * The notification sent from the backend module ("Notifications" > "Send").
 */
final class BackendUserSentMessageToFrontendUserTest extends AbstractNotificationsFunctionalTestCase
{
    private function createUser(): NotifiableFrontendUser
    {
        return (new NotifiableFrontendUser())
            ->setEmail('jane.doe@example.com')
            ->setFirstName('Jane')
            ->setLastName('Doe');
    }

    #[Test]
    public function toMailRendersTheFluidEmailTemplate(): void
    {
        $notification = new BackendUserSentMessageToFrontendUser('Maintenance', 'The site will be down tonight.', NotificationLevel::LEVEL_WARNING, null);

        $mail = $notification->toMail($this->createUser());

        self::assertStringContainsString('Notification tests', (string)$mail->getSubject());
        $html = (string)$mail->getHtmlBody();
        self::assertStringContainsString('Maintenance', $html);
        self::assertStringContainsString('The site will be down tonight.', $html);
    }

    #[Test]
    public function toMailRequiresARequestOrASiteInCliContext(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        $notification = new BackendUserSentMessageToFrontendUser('Subject', 'Body', NotificationLevel::LEVEL_INFO, null);

        $this->expectException(SiteNotFoundException::class);
        $this->expectExceptionCode(1746276915);

        $notification->toMail($this->createUser());
    }

    #[Test]
    public function sendingToFrontendUsersDeliversEmailAndDatabaseNotifications(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/fe_users.csv');
        $users = $this->get(NotifiableFrontendUserRepository::class)->findByUids([1, 2])->toArray();

        $this->get(NotificationDispatcherInterface::class)->send(
            $users,
            new BackendUserSentMessageToFrontendUser('Hello', 'World', NotificationLevel::LEVEL_NOTICE, null)
        );

        self::assertCount(2, CapturingTransport::$messages);
        foreach (CapturingTransport::$messages as $sent) {
            $email = $sent->getOriginalMessage();
            self::assertInstanceOf(Email::class, $email);
            self::assertStringContainsString('World', (string)$email->getHtmlBody());
        }

        $rows = $this->fetchNotificationRows();
        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertSame(BackendUserSentMessageToFrontendUser::class, $row['type']);
            self::assertSame(NotificationLevel::LEVEL_NOTICE, (int)$row['level']);
            $data = json_decode($row['data'], true);
            self::assertSame('Hello', $data['subject']);
            self::assertSame('World', $data['message']);
        }
    }

    #[Test]
    public function channelsSelectedInTheBackendAreRespected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/fe_users.csv');
        $users = $this->get(NotifiableFrontendUserRepository::class)->findByUids([1])->toArray();

        $this->get(NotificationDispatcherInterface::class)->send(
            $users,
            new BackendUserSentMessageToFrontendUser('Hello', 'World', NotificationLevel::LEVEL_INFO, null, [NotificationChannel::CHANNEL_DATABASE])
        );

        self::assertSame([], CapturingTransport::$messages);
        self::assertCount(1, $this->fetchNotificationRows());
    }
}
