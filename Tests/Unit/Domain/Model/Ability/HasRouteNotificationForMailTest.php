<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Domain\Model\Ability;

use Lex\Notifications\Tests\Unit\Fixtures\EmailOnlyRecipient;
use Lex\Notifications\Tests\Unit\Fixtures\LastNameOnlyRecipient;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class HasRouteNotificationForMailTest extends UnitTestCase
{
    public static function namesDataProvider(): iterable
    {
        yield 'first and last name' => ['Jane', 'Doe', 'Jane Doe'];
        yield 'first name only' => ['Jane', '', 'Jane'];
        yield 'last name only' => ['', 'Doe', 'Doe'];
        yield 'null first name' => [null, 'Doe', 'Doe'];
        yield 'no name at all' => ['', '', ''];
        yield 'both null' => [null, null, ''];
    }

    #[Test]
    #[DataProvider('namesDataProvider')]
    public function addressNameIsBuiltFromFirstAndLastName(?string $firstName, ?string $lastName, string $expected): void
    {
        $expectedEmail = 'julia@example.com';
        $address = (new NotifiableUser(1, $expectedEmail, $firstName, $lastName))->routeNotificationForMail();

        self::assertSame($expectedEmail, $address->getAddress());
        self::assertSame($expected, $address->getName());
    }

    #[Test]
    public function notificationArgumentIsOptionalAndAccepted(): void
    {
        $user = new NotifiableUser();

        self::assertEquals($user->routeNotificationForMail(), $user->routeNotificationForMail(new TestNotification()));
    }

    #[Test]
    public function worksWithoutNameGetters(): void
    {
        $address = (new EmailOnlyRecipient('contact@example.com'))->routeNotificationForMail();

        self::assertSame('contact@example.com', $address->getAddress());
        self::assertSame('', $address->getName());
    }

    #[Test]
    public function worksWithOnlyALastNameGetter(): void
    {
        $address = (new LastNameOnlyRecipient())->routeNotificationForMail();

        self::assertSame('support@example.com', $address->getAddress());
        self::assertSame('Support', $address->getName());
    }
}
