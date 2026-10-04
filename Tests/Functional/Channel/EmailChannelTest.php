<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Channel;

use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationManager;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use Lex\NotificationsTest\Mail\CapturingTransport;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;

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
    public function notifyNowSendsAnEmailThroughTheTypo3Mailer(): void
    {
        (new NotifiableUser(1, 'jane.doe@example.com', 'Jane', 'Doe'))
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertCount(1, CapturingTransport::$messages);
        $email = $this->lastEmail();
        self::assertSame('jane.doe@example.com', $email->getTo()[0]->getAddress());
        self::assertSame('Jane Doe', $email->getTo()[0]->getName());
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
        (new NotifiableUser(1, 'jane.doe@example.com'))
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL], explicitRecipient: 'override@example.com'));

        $to = $this->lastEmail()->getTo();
        self::assertCount(1, $to);
        self::assertSame('jane.doe@example.com', $to[0]->getAddress());
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

        $manager->route(NotificationChannel::CHANNEL_MAIL, 'guest@example.com')
            ->notifyNow(new TestNotification([NotificationChannel::CHANNEL_MAIL]));

        self::assertSame('guest@example.com', $this->lastEmail()->getTo()[0]->getAddress());
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
