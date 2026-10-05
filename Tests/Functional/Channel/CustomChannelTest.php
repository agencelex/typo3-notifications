<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional\Channel;

use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\NotificationDispatcherInterface;
use Lex\Notifications\NotificationManager;
use Lex\Notifications\Tests\Functional\AbstractNotificationsFunctionalTestCase;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use Lex\Notifications\Tests\Unit\Fixtures\TestNotification;
use Lex\NotificationsTest\Channel\AttributeTaggedChannel;
use Lex\NotificationsTest\Channel\YamlTaggedChannel;
use Lex\NotificationsTest\Mail\CapturingTransport;
use PHPUnit\Framework\Attributes\Test;

/**
 * Third-party channels shipped by another extension must receive notifications.
 */
final class CustomChannelTest extends AbstractNotificationsFunctionalTestCase
{
    #[Test]
    public function notificationIsDeliveredToAChannelRegisteredByAnotherExtension(): void
    {
        $user = new class () {
            use Notifiable;
            public function routeNotificationForAttributeTagged(): bool { return true; }
        };

        $user->notifyNow(new TestNotification([AttributeTaggedChannel::NAME]));

        $channel = $this->get(AttributeTaggedChannel::class);
        self::assertCount(1, $channel->sent);
        self::assertSame($user, $channel->sent[0]['notifiable']);
        self::assertSame([], CapturingTransport::$messages);
        self::assertSame([], $this->fetchNotificationRows());
    }

    #[Test]
    public function channelClassShortNameCanBeUsedInVia(): void
    {
        $channelName = basename(str_replace('\\', '/', YamlTaggedChannel::class));
        (new class () {
            use Notifiable;
            public function routeNotificationForYamlTaggedChannel(): bool { return true; }
        })->notifyNow(new TestNotification([$channelName]));

        self::assertCount(1, $this->get(YamlTaggedChannel::class)->sent);
    }

    #[Test]
    public function onDemandRouteIsAvailableToCustomChannels(): void
    {
        /** @var NotificationManager $manager */
        $manager = $this->get(NotificationDispatcherInterface::class);

        $manager->route(AttributeTaggedChannel::NAME, 'room-42')
            ->notifyNow(new TestNotification([AttributeTaggedChannel::NAME]));

        self::assertSame('room-42', $this->get(AttributeTaggedChannel::class)->sent[0]['route']);
    }
}
