<?php declare(strict_types=1);

namespace Lex\Notifications\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use Lex\Notifications\Domain\Model\Ability as Ability;

class NotifiableFrontendUser extends AbstractEntity
{
    use Ability\Notifiable;
    use Ability\HasRouteNotificationForMail;

    protected string $email = '';

    protected string $firstName = '';

    protected string $lastName = '';

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

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }
}