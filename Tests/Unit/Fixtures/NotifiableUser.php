<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;
use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\Notification;

/**
 * Plain PHP object (no Extbase) made notifiable, like an integrator would do.
 */
final class NotifiableUser
{
    use Notifiable;
    use HasRouteNotificationForMail;

    public function __construct(
        private readonly int $uid = 42,
        private readonly string $email = 'jane.doe@example.com',
        private readonly ?string $firstName = 'Jane',
        private readonly ?string $lastName = 'Doe',
        private readonly ?string $slackWebhook = null,
    ) {}

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function routeNotificationForSlack(?Notification $notification = null): ?string
    {
        return $this->slackWebhook;
    }

    public function routeNotificationForAttributeTagged(): bool { return true; }
}
