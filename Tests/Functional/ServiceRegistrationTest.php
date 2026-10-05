<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional;

use Lex\Notifications\Channel\DatabaseChannel;
use Lex\Notifications\Channel\EmailChannel;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationManager;
use Lex\NotificationsTest\Channel\AttributeTaggedChannel;
use Lex\NotificationsTest\Channel\YamlTaggedChannel;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Verifies the dependency injection contract integrators rely on.
 */
final class ServiceRegistrationTest extends AbstractNotificationsFunctionalTestCase
{
    #[Test]
    public function dispatcherInterfaceIsAPublicServiceBackedByTheManager(): void
    {
        $dispatcher = $this->get(NotificationDispatcherInterface::class);

        self::assertInstanceOf(NotificationManager::class, $dispatcher);
    }

    #[Test]
    public function dispatcherIsAvailableThroughMakeInstanceAsUsedByTheNotifiableTrait(): void
    {
        self::assertSame(
            $this->get(NotificationDispatcherInterface::class),
            GeneralUtility::makeInstance(NotificationDispatcherInterface::class)
        );
    }

    #[Test]
    public function builtInChannelsAreRegistered(): void
    {
        /** @var NotificationManager $manager */
        $manager = $this->get(NotificationDispatcherInterface::class);

        self::assertInstanceOf(EmailChannel::class, $manager->channel(NotificationChannel::CHANNEL_MAIL));
        self::assertInstanceOf(DatabaseChannel::class, $manager->channel(NotificationChannel::CHANNEL_DATABASE));
    }

    #[Test]
    public function mailIsTheDefaultChannel(): void
    {
        /** @var NotificationManager $manager */
        $manager = $this->get(NotificationDispatcherInterface::class);

        self::assertInstanceOf(EmailChannel::class, $manager->channel());
    }

    #[Test]
    public function thirdPartyChannelTaggedByAttributeIsRegisteredByName(): void
    {
        /** @var NotificationManager $manager */
        $manager = $this->get(NotificationDispatcherInterface::class);

        self::assertSame(
            $this->get(AttributeTaggedChannel::class),
            $manager->channel(AttributeTaggedChannel::NAME)
        );
    }

    #[Test]
    public function thirdPartyChannelTaggedInServicesYamlIsRegisteredByShortClassName(): void
    {
        /** @var NotificationManager $manager */
        $manager = $this->get(NotificationDispatcherInterface::class);

        self::assertSame(
            $this->get(YamlTaggedChannel::class),
            $manager->channel(basename(str_replace('\\', '/', YamlTaggedChannel::class)))
        );
    }
}
