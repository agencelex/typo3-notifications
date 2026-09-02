<?php declare(strict_types=1);

namespace Lex\Notifications\Notification;

use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Lex\Notifications\Extension;
use Lex\Notifications\Notification;
use Lex\Notifications\NotificationChannel;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BackendUserSentMessageToFrontendUser extends Notification implements ShouldQueue
{
    public function __construct(
        protected readonly string  $subject,
        protected readonly string  $message,
        protected int              $level,
        protected readonly ?string $link,
        protected readonly array $channels = []
    )
    {}

    public function via(object $notifiable): array
    {
        return empty($this->channels) ? [
            NotificationChannel::CHANNEL_MAIL,
            NotificationChannel::CHANNEL_DATABASE
        ] : $this->channels;
    }

    /**
     * @throws SiteNotFoundException
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = new MailMessage();
        $mail
            ->subject(LocalizationUtility::translate('notification.email.subject', Extension::KEY, [$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']]))
            ->html($this->renderEmailTemplate($notifiable));

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'level' => $this->level,
            'subject' => $this->subject,
            'message' => $this->message,
            'link' => $this->link,
            'creation_datetime' => (new DateTime("now", new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @throws SiteNotFoundException
     */
    protected function renderEmailTemplate(object $notifiable): string
    {
        $rootPath = 'EXT:' . Extension::KEY . '/Resources/Private';

        $request = $this->getRequest();

        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        // Add our own TemplatePaths.
        // Each array of paths (templates, partials and layout) will be sorted in the class TemplatePaths
        // using by ArrayUtility::sortArrayWithIntegerKeys
        $view = $viewFactory->create(new ViewFactoryData(
            templateRootPaths: array_merge_recursive($GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'] ?? [], [1746276915 => "$rootPath/Templates/Email/"]),
            partialRootPaths: array_merge_recursive($GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'] ?? [], [1746276915 => "$rootPath/Partials/Email/"]),
            layoutRootPaths: array_merge_recursive($GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'] ?? [], [1746276915 => "$rootPath/Layouts/Email/"]),
            request: $request
        ));
        $view->assignMultiple([
            'notifiable' => $notifiable,
            'level' => $this->level,
            'subject' => $this->subject,
            'message' => $this->message,
            'link' => $this->link,
            'typo3' => [
                'sitename' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                'formats' => [
                    'date' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
                    'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
                ],
                'systemConfiguration' => $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'],
                'information' => GeneralUtility::makeInstance(Typo3Information::class),
            ],
            'normalizedParams' => $request->getAttribute('normalizedParams'),
            'extensionName' => Extension::KEY
        ]);

        $className = basename(str_replace('\\', '/', self::class)); //=> 'BackendUserSentMessageToFrontendUser'
        return $view->render($className);
    }

    /**
     * @throws SiteNotFoundException
     */
    protected function getRequest(): ServerRequestInterface
    {
        if(isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof RequestInterface) {
            // We are in MVC context with Extbase
            return $GLOBALS['TYPO3_REQUEST'];
        }

        // Site is mandatory otherwise there is no way a proper frontend request

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();

        if (!empty($sites)) {
            $site = $sites[array_key_first($sites)];
            return (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('normalizedParams', new NormalizedParams(
                [
                    'HTTP_HOST' => $site->getBase()->getHost(),
                    'HTTPS' => $site->getBase()->getScheme() === 'https' ? 'on' : 'off',
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS'],
                Environment::getCurrentScript(),
                Environment::getPublicPath()
            ))
                ->withAttribute('site', $site);
        }

        throw new SiteNotFoundException("A site is required for this view on CLI context.", 1746276915);
    }
}