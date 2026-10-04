<?php

namespace Lex\Notifications\Domain\Model\Ability;

use Lex\Notifications\Notification;
use Symfony\Component\Mime\Address;

trait HasRouteNotificationForMail
{
    abstract public function getEmail(): string;

    public function routeNotificationForMail(?Notification $notification = null): Address
    {
        $firstName = method_exists($this, 'getFirstName') ? $this->getFirstName() : null;
        $lastName = method_exists($this, 'getLastName') ? $this->getLastName() : null;
        $name = empty($firstName) && empty($lastName) ? '' : trim(join(' ', array_filter([$firstName, $lastName])));

        return new Address(
            $this->getEmail(),
            $name
        );
    }
}