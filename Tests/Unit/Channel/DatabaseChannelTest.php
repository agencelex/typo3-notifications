<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Channel;

use Lex\Notifications\Channel\ChannelInterface;
use Lex\Notifications\Channel\DatabaseChannel;
use Lex\Notifications\Domain\Model\DatabaseNotification;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationLevel;
use Lex\Notifications\Tests\Unit\Fixtures\BareNotification;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseChannelTest extends UnitTestCase
{
    #[Test]
    public function isAChannelNamedDatabase(): void
    {
        $channel = new DatabaseChannel($this->createStub(PersistenceManager::class));

        self::assertInstanceOf(ChannelInterface::class, $channel);
        self::assertSame(NotificationChannel::CHANNEL_DATABASE, $channel->getName());
    }

    #[Test]
    public function sendPersistsADatabaseNotification(): void
    {
        $stored = null;
        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::once())
            ->method('add')
            ->willReturnCallback(static function (object $object) use (&$stored): void {
                $stored = $object;
            });
        $persistenceManager->expects(self::once())->method('persistAll');

        $notification = new TestNotification(payload: ['subject' => 'Hi', 'count' => 3], level: NotificationLevel::LEVEL_WARNING);
        (new DatabaseChannel($persistenceManager))->send(new NotifiableUser(uid: 7), $notification);

        self::assertInstanceOf(DatabaseNotification::class, $stored);
        self::assertSame(TestNotification::class, $stored->getType());
        self::assertSame(7, $stored->getNotifiableId());
        self::assertSame(NotifiableUser::class, $stored->getNotifiableType());
        self::assertSame(NotificationLevel::LEVEL_WARNING, $stored->getLevel());
        self::assertSame(['subject' => 'Hi', 'count' => 3], $stored->getDataAsArray());
        self::assertNull($stored->getReadAt());
        self::assertSame(-1, $stored->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID), 'Notifications must be stored for all languages');
    }

    #[Test]
    public function sendThrowsWhenToDatabaseIsNotImplemented(): void
    {
        $persistenceManager = $this->createMock(PersistenceManager::class);
        $persistenceManager->expects(self::never())->method('add');

        $this->expectException(NotImplementedMethodException::class);

        (new DatabaseChannel($persistenceManager))->send(new NotifiableUser(), new BareNotification());
    }
}
