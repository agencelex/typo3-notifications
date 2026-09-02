<?php

namespace Lex\Notifications\Domain\Model\Ability;

use Symfony\Component\Mime\Address;

trait HasRouteNotificationForMail
{
    abstract public function getEmail(): string;

    abstract public function getFirstName(): ?string;

    abstract public function getLastName(): ?string;

    public function routeNotificationForMail(): Address
    {
        return new Address(
            $this->getEmail(),
            trim(join(' ', array_filter([$this->getFirstName(), $this->getLastName()])))
        );
    }
}