<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Domain\Model;

use DateTime;
use Lex\Notifications\Domain\Model\Message;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MessageTest extends UnitTestCase
{
    #[Test]
    public function hasSensibleDefaults(): void
    {
        $subject = new Message();

        self::assertSame(0, $subject->getLevel());
        self::assertSame('', $subject->getSubject());
        self::assertSame('', $subject->getMessage());
        self::assertSame('', $subject->getLink());
        self::assertSame('', $subject->getReceivers());
        self::assertSame('', $subject->getExcludedRecipients());
        self::assertSame('', $subject->getChannels());
        self::assertNull($subject->getSentAt());
        self::assertNull($subject->getCruser());
        self::assertNull($subject->getCrdate());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $sentAt = new DateTime('2025-01-01');
        $crdate = new DateTime('2024-12-01');

        $subject = (new Message())
            ->setLevel(2)
            ->setSubject('Subject')
            ->setMessage('Body')
            ->setLink('t3://page?uid=1')
            ->setReceivers('fe_users_1,fe_groups_2')
            ->setExcludedRecipients('fe_users_3')
            ->setChannels('mail,database')
            ->setSentAt($sentAt)
            ->setCruser(5)
            ->setCrdate($crdate);

        self::assertSame(2, $subject->getLevel());
        self::assertSame('Subject', $subject->getSubject());
        self::assertSame('Body', $subject->getMessage());
        self::assertSame('t3://page?uid=1', $subject->getLink());
        self::assertSame('fe_users_1,fe_groups_2', $subject->getReceivers());
        self::assertSame('fe_users_3', $subject->getExcludedRecipients());
        self::assertSame('mail,database', $subject->getChannels());
        self::assertSame($sentAt, $subject->getSentAt());
        self::assertSame(5, $subject->getCruser());
        self::assertSame($crdate, $subject->getCrdate());
    }
}
