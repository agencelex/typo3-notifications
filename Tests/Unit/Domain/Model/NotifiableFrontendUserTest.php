<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Domain\Model;

use Lex\Notifications\Domain\Model\NotifiableFrontendUser;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NotifiableFrontendUserTest extends UnitTestCase
{
    #[Test]
    public function isAnExtbaseEntity(): void
    {
        self::assertInstanceOf(AbstractEntity::class, new NotifiableFrontendUser());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $user = (new NotifiableFrontendUser())
            ->setEmail('jane@example.com')
            ->setFirstName('Jane')
            ->setLastName('Doe');

        self::assertSame('jane@example.com', $user->getEmail());
        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
    }

    #[Test]
    public function mailRouteContainsEmailAndFullName(): void
    {
        $user = (new NotifiableFrontendUser())
            ->setEmail('jane@example.com')
            ->setFirstName('Jane')
            ->setLastName('Doe');

        $route = $user->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, new TestNotification());

        self::assertInstanceOf(Address::class, $route);
        self::assertSame('jane@example.com', $route->getAddress());
        self::assertSame('Jane Doe', $route->getName());
    }
}
