<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Domain\Model\Ability\CanSendMailMessage;
use Lex\Notifications\Notification;
use Lex\Notifications\NotificationLevel;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Notification relying on the CanSendMailMessage trait, exposing its protected helpers for tests.
 */
final class MailMessageNotification extends Notification
{
    use CanSendMailMessage;

    public function __construct(
        protected readonly string $subject = 'A subject',
        protected readonly string $message = 'A message',
        protected readonly ?string $link = null,
        int $level = NotificationLevel::LEVEL_INFO,
    ) {
        $this->level = $level;
    }

    public function callRenderEmailTemplate(object $notifiable): string
    {
        return $this->renderEmailTemplate($notifiable);
    }

    public function callGetRequest(): ServerRequestInterface
    {
        return $this->getRequest();
    }
}
