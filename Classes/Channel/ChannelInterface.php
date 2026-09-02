<?php declare(strict_types=1);

namespace Lex\Notifications\Channel;

use Lex\Notifications\Notification;

interface ChannelInterface
{
    public function send(object $notifiable, Notification $notification): void;
}