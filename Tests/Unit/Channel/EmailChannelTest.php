<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Channel;

use Lex\Notifications\AnonymousNotifiable;
use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Channel\EmailChannel;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EmailChannelTest extends UnitTestCase
{
    private ?MailMessage $sentMessage = null;

    private function createChannel(): EmailChannel
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (RawMessage $message): void {
                self::assertInstanceOf(MailMessage::class, $message);
                $this->sentMessage = $message;
            });

        return new EmailChannel($mailer);
    }

    #[Test]
    public function isAChannelNamedMail(): void
    {
        $channel = new EmailChannel($this->createStub(MailerInterface::class));

        self::assertInstanceOf(ChannelInterface::class, $channel);
        self::assertSame(NotificationChannel::CHANNEL_MAIL, $channel->getName());
    }

    #[Test]
    public function recipientIsResolvedFromTheNotifiableWhenNotSet(): void
    {
        $this->createChannel()->send(new NotifiableUser(1, 'jane@example.com', 'Jane', 'Doe'), new TestNotification());

        $to = $this->sentMessage->getTo();
        self::assertCount(1, $to);
        self::assertSame('jane@example.com', $to[0]->getAddress());
        self::assertSame('Jane Doe', $to[0]->getName());
    }

    #[Test]
    public function explicitRecipientOfTheMailMessageIsIgnored(): void
    {
        $notification = new TestNotification(explicitRecipient: 'override@example.com');

        $this->createChannel()->send(new NotifiableUser(1, 'jane@example.com'), $notification);

        $to = $this->sentMessage->getTo();
        self::assertCount(1, $to);
        self::assertSame('jane@example.com', $to[0]->getAddress());
    }

    #[Test]
    public function anonymousNotifiableStringRouteIsUsed(): void
    {
        $notifiable = (new AnonymousNotifiable())->route(NotificationChannel::CHANNEL_MAIL, 'anonymous@example.com');

        $this->createChannel()->send($notifiable, new TestNotification());

        self::assertSame('anonymous@example.com', $this->sentMessage->getTo()[0]->getAddress());
    }

    #[Test]
    public function anonymousNotifiableAddressRouteIsUsed(): void
    {
        $notifiable = (new AnonymousNotifiable())->route(NotificationChannel::CHANNEL_MAIL, new Address('anonymous@example.com', 'Anonymous'));

        $this->createChannel()->send($notifiable, new TestNotification());

        self::assertSame('Anonymous', $this->sentMessage->getTo()[0]->getName());
    }

    #[Test]
    public function subjectAndBodyOfTheNotificationAreSent(): void
    {
        $this->createChannel()->send(new NotifiableUser(), new TestNotification());

        self::assertSame('Test notification', $this->sentMessage->getSubject());
        self::assertSame('Hello', $this->sentMessage->getTextBody());
    }
}
