<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Channel;

use Lex\Notifications\Domain\Model\Ability\CanSendMailMessage;
use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\Notification;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationManager;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use Lex\NotificationsTest\Mail\CapturingTransport;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Mail\MailMessage;

final class EmailChannelTest extends AbstractNotificationsFunctionalTestCase
{
    private function lastEmail(): Email
    {
        self::assertNotEmpty(CapturingTransport::$messages, 'No email has been sent');
        $message = end(CapturingTransport::$messages)->getOriginalMessage();
        self::assertInstanceOf(Email::class, $message);

        return $message;
    }

    #[Test]
    public function notifiableEmailIsUsedWhenTheNotificationIsSent(): void
    {
        $expectedEmail = 'johnnyboy@example.com';
        $expectedFirstName = 'Johnny';
        $expectedLastName = 'Boy';
        $expectedName = $expectedFirstName . ' ' . $expectedLastName;
        (new NotifiableUser(1, $expectedEmail, $expectedFirstName, $expectedLastName))
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        $email = $this->lastEmail();
        self::assertSame($expectedEmail, $email->getTo()[0]->getAddress());
        self::assertSame($expectedName, $email->getTo()[0]->getName());
        self::assertSame('Test notification', $email->getSubject());
    }

    #[Test]
    public function notifyNowSendsAnEmailThroughTheTypo3Mailer(): void
    {
        $expectedEmail = 'jane.doe@example.com';
        $expectedFirstName = 'Jane';
        $expectedLastName = 'Doe';
        $expectedName = $expectedFirstName . ' ' . $expectedLastName;
        (new NotifiableUser(1, $expectedEmail, $expectedFirstName, $expectedLastName))
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(1, CapturingTransport::$messages);
        $email = $this->lastEmail();
        self::assertSame($expectedEmail, $email->getTo()[0]->getAddress());
        self::assertSame($expectedName, $email->getTo()[0]->getName());
        self::assertSame('Test notification', $email->getSubject());
    }

    #[Test]
    public function systemSenderIsUsedWhenTheNotificationDefinesNone(): void
    {
        (new NotifiableUser())->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertSame('noreply@example.com', $this->lastEmail()->getFrom()[0]->getAddress());
    }

    #[Test]
    public function explicitMailMessageRecipientIsIgnored(): void
    {
        $expectedEmail = 'july.doe@example.com';
        (new NotifiableUser(1, $expectedEmail))
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL], explicitRecipient: 'override@example.com'));

        $to = $this->lastEmail()->getTo();
        self::assertCount(1, $to);
        self::assertSame($expectedEmail, $to[0]->getAddress());
    }

    #[Test]
    public function notifiableCanDelegateRecipientToNotification(): void
    {
        $notifiable = new class()
        {
            use Notifiable;
            public function routeNotificationForMail(Notification $notification): string { return $notification->getEmail(); }
        };

        $expectedEmail = 'charles.doe@example.com';
        $expectedSubject = 'Test that notifiable can delegate recipient choice to notification';
        $notification = new class($expectedEmail, $expectedSubject) extends Notification
        {
            use CanSendMailMessage;
            public function __construct(
                private readonly string $email,
                private readonly string $subject,
            ) {}
            public function via(object $notifiable): array { return [NotificationChannel::CHANNEL_MAIL]; }
            public function toMail(object $notifiable): MailMessage
            {
                return (new MailMessage())
                    ->subject($this->subject)
                    ->text('Hello');
            }

            public function getEmail(): string { return $this->email; }
        };

        $notifiable->notifyNow($notification);

        $email = $this->lastEmail();
        $to = $email->getTo();
        self::assertCount(1, $to);
        self::assertSame($expectedEmail, $to[0]->getAddress());
        self::assertSame($expectedSubject, $email->getSubject());
    }

    #[Test]
    public function oneEmailIsSentPerNotifiable(): void
    {
        $this->get(NotificationDispatcherInterface::class)->sendNow(
            [new NotifiableUser(1, 'a@example.com'), new NotifiableUser(2, 'b@example.com')],
            new TestNotification([NotificationChannel::CHANNEL_MAIL])
        );

        $recipients = array_map(
            static fn($sent): string => $sent->getOriginalMessage()->getTo()[0]->getAddress(),
            CapturingTransport::$messages
        );
        self::assertSame(['a@example.com', 'b@example.com'], $recipients);
    }

    #[Test]
    public function onDemandNotificationIsSentToTheGivenAddress(): void
    {
        /** @var NotificationManager $manager */
        $manager = $this->get(NotificationDispatcherInterface::class);

        $expectedRoute = 'guest@example.com';
        $manager->route(NotificationChannel::CHANNEL_MAIL, $expectedRoute)
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertSame($expectedRoute, $this->lastEmail()->getTo()[0]->getAddress());
        self::assertSame([], $this->fetchNotificationRows());
    }

    #[Test]
    public function mailAndDatabaseChannelsCanBeCombined(): void
    {
        (new NotifiableUser(uid: 5))->notifyNow(new TestNotification());

        self::assertCount(1, CapturingTransport::$messages);
        self::assertCount(1, $this->fetchNotificationRows());
    }
}
