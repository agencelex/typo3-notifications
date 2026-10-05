<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Domain\Model\Ability;

use Lex\Notifications\Extension;
use Lex\Notifications\NotificationLevel;
use Lex\Notifications\Tests\Unit\Fixtures\MailMessageNotification;
use Lex\Notifications\Tests\Unit\Fixtures\NotifiableUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CanSendMailMessageTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'My site';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] = 'd-m-y';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] = 'H:i';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'] = ['some_ext' => ['foo' => 'bar']];
        // Avoids Typo3Information resolving a LanguageServiceFactory
        $GLOBALS['LANG'] = $this->createStub(LanguageService::class);
        unset($GLOBALS['TYPO3_REQUEST']);
    }

    // getRequest()

    #[Test]
    public function getRequestReturnsTheGlobalRequestWhenAvailable(): void
    {
        $request = new ServerRequest('https://example.com/');
        $GLOBALS['TYPO3_REQUEST'] = $request;

        self::assertSame($request, (new MailMessageNotification())->callGetRequest());
    }

    #[Test]
    public function getRequestIgnoresAGlobalRequestThatIsNotAServerRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new \stdClass();
        $this->registerSites([]);

        $this->expectException(SiteNotFoundException::class);
        $this->expectExceptionCode(1746276915);

        (new MailMessageNotification())->callGetRequest();
    }

    #[Test]
    public function getRequestThrowsWhenNoRequestAndNoSiteAreAvailable(): void
    {
        $this->registerSites([]);

        $this->expectException(SiteNotFoundException::class);
        $this->expectExceptionCode(1746276915);

        (new MailMessageNotification())->callGetRequest();
    }

    public static function siteBaseDataProvider(): array
    {
        return [
            'https' => ['https://www.example.com/', 'www.example.com', true],
            'http' => ['http://example.org/', 'example.org', false],
        ];
    }

    #[Test]
    #[DataProvider('siteBaseDataProvider')]
    public function getRequestBuildsAFrontendRequestFromTheFirstSite(string $base, string $expectedHost, bool $expectedHttps): void
    {
        $this->initializeEnvironment();
        $firstSite = $this->createSite($base);
        $this->registerSites(['first' => $firstSite, 'second' => $this->createSite('https://second.example.com/')]);

        $request = (new MailMessageNotification())->callGetRequest();

        self::assertSame(SystemEnvironmentBuilder::REQUESTTYPE_FE, $request->getAttribute('applicationType'));
        self::assertSame($firstSite, $request->getAttribute('site'));
        $normalizedParams = $request->getAttribute('normalizedParams');
        self::assertInstanceOf(NormalizedParams::class, $normalizedParams);
        self::assertSame($expectedHost, $normalizedParams->getHttpHost());
        self::assertSame($expectedHttps, $normalizedParams->isHttps());
    }

    // renderEmailTemplate()

    #[Test]
    public function renderEmailTemplateRendersTheTemplateNamedAfterTheNotificationClass(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/');
        $view = $this->createMock(ViewInterface::class);
        $view->method('assignMultiple')->willReturnSelf();
        $view->expects(self::once())->method('render')->with('MailMessageNotification')->willReturn('<p>rendered</p>');
        $this->registerViewFactory($view);

        self::assertSame('<p>rendered</p>', (new MailMessageNotification())->callRenderEmailTemplate(new NotifiableUser()));
    }

    #[Test]
    public function renderEmailTemplateAssignsTheNotificationAndSystemVariables(): void
    {
        $normalizedParams = new NormalizedParams(['HTTP_HOST' => 'example.com'], [], '', '');
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/'))->withAttribute('normalizedParams', $normalizedParams);
        $notifiable = new NotifiableUser();

        $assigned = [];
        $view = $this->createStub(ViewInterface::class);
        $view->method('assignMultiple')->willReturnCallback(function (array $values) use (&$assigned, $view) {
            $assigned = $values;
            return $view;
        });
        $view->method('render')->willReturn('');
        $this->registerViewFactory($view);

        (new MailMessageNotification('Hello', 'World', 'https://example.com/link', NotificationLevel::LEVEL_ERROR))
            ->callRenderEmailTemplate($notifiable);

        self::assertSame($notifiable, $assigned['notifiable']);
        self::assertSame(NotificationLevel::LEVEL_ERROR, $assigned['level']);
        self::assertSame('Hello', $assigned['subject']);
        self::assertSame('World', $assigned['message']);
        self::assertSame('https://example.com/link', $assigned['link']);
        self::assertSame('My site', $assigned['typo3']['sitename']);
        self::assertSame(['date' => 'd-m-y', 'time' => 'H:i'], $assigned['typo3']['formats']);
        self::assertSame(['some_ext' => ['foo' => 'bar']], $assigned['typo3']['systemConfiguration']);
        self::assertInstanceOf(Typo3Information::class, $assigned['typo3']['information']);
        self::assertSame($normalizedParams, $assigned['normalizedParams']);
        self::assertSame(Extension::KEY, $assigned['extensionName']);
    }

    #[Test]
    public function renderEmailTemplateAppendsTheExtensionPathsToTheConfiguredMailPaths(): void
    {
        $request = new ServerRequest('https://example.com/');
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'] = [0 => 'EXT:core/Resources/Private/Templates/Email/', 10 => 'EXT:site/Templates/Email/'];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'] = [0 => 'EXT:core/Resources/Private/Partials/'];
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'] = [0 => 'EXT:core/Resources/Private/Layouts/'];

        $view = $this->createStub(ViewInterface::class);
        $view->method('assignMultiple')->willReturnSelf();
        $view->method('render')->willReturn('');

        $viewFactory = $this->createMock(ViewFactoryInterface::class);
        $viewFactory->expects(self::once())->method('create')
            ->with(self::callback(function (ViewFactoryData $data) use ($request): bool {
                $rootPath = 'EXT:' . Extension::KEY . '/Resources/Private';
                // Extension paths come last, so they take precedence over the configured ones
                self::assertSame(['EXT:core/Resources/Private/Templates/Email/', 'EXT:site/Templates/Email/', "$rootPath/Templates/Email/"], array_values($data->templateRootPaths));
                self::assertSame(['EXT:core/Resources/Private/Partials/', "$rootPath/Partials/Email/"], array_values($data->partialRootPaths));
                self::assertSame(['EXT:core/Resources/Private/Layouts/', "$rootPath/Layouts/Email/"], array_values($data->layoutRootPaths));
                self::assertSame($request, $data->request);
                return true;
            }))
            ->willReturn($view);
        GeneralUtility::addInstance(ViewFactoryInterface::class, $viewFactory);

        (new MailMessageNotification())->callRenderEmailTemplate(new NotifiableUser());
    }

    #[Test]
    public function renderEmailTemplateOnlyUsesTheExtensionPathsWhenNoMailPathsAreConfigured(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/');
        unset($GLOBALS['TYPO3_CONF_VARS']['MAIL']);

        $view = $this->createStub(ViewInterface::class);
        $view->method('assignMultiple')->willReturnSelf();
        $view->method('render')->willReturn('');

        $viewFactory = $this->createMock(ViewFactoryInterface::class);
        $viewFactory->expects(self::once())->method('create')
            ->with(self::callback(function (ViewFactoryData $data): bool {
                $rootPath = 'EXT:' . Extension::KEY . '/Resources/Private';
                self::assertSame(["$rootPath/Templates/Email/"], array_values($data->templateRootPaths));
                self::assertSame(["$rootPath/Partials/Email/"], array_values($data->partialRootPaths));
                self::assertSame(["$rootPath/Layouts/Email/"], array_values($data->layoutRootPaths));
                return true;
            }))
            ->willReturn($view);
        GeneralUtility::addInstance(ViewFactoryInterface::class, $viewFactory);

        (new MailMessageNotification())->callRenderEmailTemplate(new NotifiableUser());
    }

    #[Test]
    public function renderEmailTemplateThrowsWithoutRequestAndSite(): void
    {
        $this->registerSites([]);

        $this->expectException(SiteNotFoundException::class);
        $this->expectExceptionCode(1746276915);

        (new MailMessageNotification())->callRenderEmailTemplate(new NotifiableUser());
    }

    // toMail()

    #[Test]
    public function toMailReturnsAMailMessageWithTranslatedSubjectAndRenderedHtml(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/');

        $languageService = $this->createMock(LanguageService::class);
        $languageService->expects(self::once())->method('translate')
            ->with('notification.email.subject', Extension::KEY . '.messages', ['My site'])
            ->willReturn('New message from My site');
        $this->registerLanguageService($languageService);

        $view = $this->createStub(ViewInterface::class);
        $view->method('assignMultiple')->willReturnSelf();
        $view->method('render')->willReturn('<p>rendered</p>');
        $this->registerViewFactory($view);

        $mail = (new MailMessageNotification())->toMail(new NotifiableUser());

        self::assertInstanceOf(MailMessage::class, $mail);
        self::assertSame('New message from My site', $mail->getSubject());
        self::assertSame('<p>rendered</p>', $mail->getHtmlBody());
        self::assertSame([], $mail->getTo(), 'Recipient is left to the mail channel');
    }

    // Helpers

    /**
     * NormalizedParams needs the current script, which is not set in unit test context.
     */
    private function initializeEnvironment(): void
    {
        Environment::initialize(
            new ApplicationContext('Testing'),
            true,
            true,
            '/var/www/html',
            '/var/www/html/public',
            '/var/www/html/var',
            '/var/www/html/config',
            '/var/www/html/public/index.php',
            'UNIX'
        );
    }

    private function createSite(string $base): Site
    {
        $site = $this->createStub(Site::class);
        $site->method('getBase')->willReturn(new Uri($base));
        return $site;
    }

    /**
     * @param array<string, Site> $sites
     */
    private function registerSites(array $sites): void
    {
        $siteFinder = $this->createStub(SiteFinder::class);
        $siteFinder->method('getAllSites')->willReturn($sites);
        GeneralUtility::addInstance(SiteFinder::class, $siteFinder);
    }

    private function registerViewFactory(ViewInterface $view): void
    {
        $viewFactory = $this->createStub(ViewFactoryInterface::class);
        $viewFactory->method('create')->willReturn($view);
        GeneralUtility::addInstance(ViewFactoryInterface::class, $viewFactory);
    }

    private function registerLanguageService(LanguageService $languageService): void
    {
        $locales = $this->createStub(Locales::class);
        $locales->method('createLocaleFromRequest')->willReturn(new Locale('en'));
        GeneralUtility::setSingletonInstance(Locales::class, $locales);

        $languageServiceFactory = $this->createStub(LanguageServiceFactory::class);
        $languageServiceFactory->method('create')->willReturn($languageService);
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactory);
    }
}
