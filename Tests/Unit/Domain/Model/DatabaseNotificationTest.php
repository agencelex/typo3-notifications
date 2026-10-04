<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Domain\Model;

use DateTime;
use Lex\Notifications\Domain\Model\DatabaseNotification;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseNotificationTest extends UnitTestCase
{
    #[Test]
    public function hasSensibleDefaults(): void
    {
        $subject = new DatabaseNotification();

        self::assertSame('', $subject->getType());
        self::assertSame('', $subject->getNotifiableType());
        self::assertSame(0, $subject->getNotifiableId());
        self::assertSame(0, $subject->getLevel());
        self::assertNull($subject->getData());
        self::assertSame([], $subject->getDataAsArray());
        self::assertNull($subject->getReadAt());
        self::assertNull($subject->getCreatedAt());
        self::assertNull($subject->getDiffCreatedAtForHumans());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $readAt = new DateTime('2025-01-01 10:00:00');
        $createdAt = new DateTime('2024-12-31 10:00:00');

        $subject = (new DatabaseNotification())
            ->setType('Foo')
            ->setNotifiableType('Bar')
            ->setNotifiableId(3)
            ->setLevel(4)
            ->setData('{"a":1}')
            ->setReadAt($readAt)
            ->setCreatedAt($createdAt);

        self::assertSame('Foo', $subject->getType());
        self::assertSame('Bar', $subject->getNotifiableType());
        self::assertSame(3, $subject->getNotifiableId());
        self::assertSame(4, $subject->getLevel());
        self::assertSame('{"a":1}', $subject->getData());
        self::assertSame($readAt, $subject->getReadAt());
        self::assertSame($createdAt, $subject->getCreatedAt());
    }

    #[Test]
    public function dataRoundTripsThroughJson(): void
    {
        $data = ['subject' => 'Hello', 'nested' => ['ä' => 'ü'], 'link' => null, 'count' => 2];

        $subject = (new DatabaseNotification())->setDataFromArray($data);

        self::assertJson($subject->getData());
        self::assertSame($data, $subject->getDataAsArray());
    }

    #[Test]
    public function markAsReadSetsTheReadDate(): void
    {
        $before = new DateTime();
        $subject = (new DatabaseNotification())->markAsRead();

        self::assertInstanceOf(DateTime::class, $subject->getReadAt());
        self::assertGreaterThanOrEqual($before->getTimestamp(), $subject->getReadAt()->getTimestamp());
    }

    #[Test]
    public function diffCreatedAtForHumansIsReturnedWhenCreatedAtIsSet(): void
    {
        $subject = (new DatabaseNotification())->setCreatedAt(new DateTime('-2 hours'));

        $diff = $subject->getDiffCreatedAtForHumans();

        self::assertIsString($diff);
        self::assertNotSame('', $diff);
    }
}
