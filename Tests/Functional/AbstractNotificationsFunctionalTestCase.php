<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Functional;

use Lex\NotificationsTest\Mail\CapturingTransport;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Boots a TYPO3 instance with lex_notifications and a fixture extension acting as a
 * third-party integrator (custom channels, in-memory mail transport).
 */
abstract class AbstractNotificationsFunctionalTestCase extends FunctionalTestCase
{
    protected const NOTIFICATION_TABLE = 'tx_lexnotifications_domain_model_notification';

    protected array $coreExtensionsToLoad = [
        'extbase',
        'fluid',
        'frontend',
    ];

    protected array $testExtensionsToLoad = [
        'agencelex/notifications',
        __DIR__ . '/Fixtures/Extensions/notifications_test',
    ];

    protected array $configurationToUseInTestInstance = [
        'MAIL' => [
            'transport' => CapturingTransport::class,
            'defaultMailFromAddress' => 'noreply@example.com',
            'defaultMailFromName' => 'TYPO3 tests',
        ],
        'SYS' => [
            'sitename' => 'Notification tests',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        CapturingTransport::reset();

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams([
                'HTTP_HOST' => 'example.com',
                'HTTPS' => 'on',
            ]));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        CapturingTransport::reset();
        parent::tearDown();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchNotificationRows(): array
    {
        return $this->getConnectionPool()
            ->getConnectionForTable(self::NOTIFICATION_TABLE)
            ->select(['*'], self::NOTIFICATION_TABLE, [], [], ['uid' => 'ASC'])
            ->fetchAllAssociative();
    }
}
