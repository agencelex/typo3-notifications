<?php declare(strict_types=1);

namespace Lex\Notifications\Channel;

use Lex\Notifications\Notification;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class EmailChannel implements ChannelInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toMail($notifiable);

        if (empty($message->getTo()) && method_exists($notifiable, 'routeNotificationForMail')) {
            $emailAddress = $notifiable->routeNotificationForMail();
            $message->to($emailAddress);
        }

        $message->send();
    }
}