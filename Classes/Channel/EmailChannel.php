<?php declare(strict_types=1);

namespace Lex\Notifications\Channel;

use Lex\Notifications\NotificationChannel;
use TYPO3\CMS\Core\Mail\MailerInterface;
use Lex\Notifications\Notification;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class EmailChannel implements ChannelInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $recipient = $notifiable->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, $notification);

        if(!empty($recipient)) {
            $message = $notification->toMail($notifiable);
            $message->to($recipient);
            $this->mailer->send($message);
        }
    }

    public function getName(): string { return NotificationChannel::CHANNEL_MAIL; }
}