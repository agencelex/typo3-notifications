<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;

/**
 * Notifiable exposing getLastName() but not getFirstName().
 */
final class LastNameOnlyRecipient
{
    use HasRouteNotificationForMail;

    public function getEmail(): string
    {
        return 'support@example.com';
    }

    public function getLastName(): string
    {
        return 'Support';
    }
}
