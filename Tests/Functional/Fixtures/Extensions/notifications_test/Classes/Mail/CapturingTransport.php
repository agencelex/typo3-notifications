<?php declare(strict_types=1);

namespace Lex\NotificationsTest\Mail;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Mail transport keeping messages in memory.
 * Configure with $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = CapturingTransport::class.
 */
final class CapturingTransport implements TransportInterface
{
    /**
     * @var list<SentMessage>
     */
    public static array $messages = [];

    public function __construct(array $mailSettings = []) {}

    public static function reset(): void
    {
        self::$messages = [];
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $sentMessage = new SentMessage($message, $envelope ?? Envelope::create($message));
        self::$messages[] = $sentMessage;

        return $sentMessage;
    }

    public function __toString(): string
    {
        return 'capturing://';
    }
}
