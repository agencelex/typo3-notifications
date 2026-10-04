<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;
use Lex\Notifications\Domain\Model\Ability\Notifiable;

/**
 * Notifiable that only exposes an email address (no first/last name getters).
 */
final class EmailOnlyRecipient
{
    use Notifiable;
    use HasRouteNotificationForMail;

    public function __construct(
        private readonly string $email = 'contact@example.com'
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }
}
