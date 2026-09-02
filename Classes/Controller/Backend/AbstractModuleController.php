<?php declare(strict_types=1);

namespace Lex\Notifications\Controller\Backend;

use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Lex\Notifications\Extension;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

abstract class AbstractModuleController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?ModuleTemplateFactory $moduleTemplateFactory = null;
    protected ?ModuleTemplate $moduleTemplate = null;
    protected ?PageRenderer $pageRenderer = null;
    protected ?IconFactory $iconFactory = null;
    protected array $extensionConfiguration = [];

    protected int $pageUid = 0;

    protected ?BackendUriBuilder $backendUriBuilder = null;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    protected function initializeAction(): void
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(Extension::KEY);
        $this->pageUid = (int)($this->request->getQueryParams()['id'] ?? 0);

        $this->initializeModuleTemplate();

        parent::initializeAction();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    public static abstract function getModuleName(): string;

    protected function translate(string $key, string $llFile = null, ?array $arguments = null): string
    {
        if(!$llFile) {
            $llFile = 'LLL:EXT:' . Extension::KEY . '/Resources/Private/Language/locallang_mod_' . static::getModuleName() . '.xlf';
        }

        return LocalizationUtility::translate($llFile . ':' . $key, Extension::KEY , $arguments);
    }

    private function initializeModuleTemplate(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request)
            ->setFlashMessageQueue($this->getFlashMessageQueue());

        $permissionClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $pageRecord = BackendUtility::readPageAccess(
            $this->pageUid,
            $permissionClause,
        );
        if ($pageRecord) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }

        $this->moduleTemplate->assignMultiple([
            'currentPid' => $this->pageUid,
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
    }

    protected function addFlashNotification(
        string $messageBody,
        string $messageTitle = '',
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK,
        bool $storeInSession = true
    ): void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageBody,
            $messageTitle,
            $severity,
            $storeInSession
        );

        $this->getFlashMessageQueue(FlashMessageQueue::NOTIFICATION_QUEUE)
            ->enqueue($flashMessage);
    }

    /**
     * Resolves frontend user ids from a mixed list of frontend users
     * and frontend user groups (e.g. "fe_users_1,fe_groups_3,fe_users_4,...")
     */
    protected function resolveFrontendUserIds(string $frontendUserGroupList): array
    {
        $elements = GeneralUtility::trimExplode(',', $frontendUserGroupList, true);
        $frontendUserIds = [];
        $frontendGroupIds = [];

        foreach ($elements as $element) {
            if (str_starts_with($element, 'fe_users_')) {
                // Current value is a uid of a fe_user record
                $frontendUserIds[] = (int)str_replace('fe_users_', '', $element);
            } elseif (str_starts_with($element, 'fe_groups_')) {
                $frontendGroupIds[] = (int)str_replace('fe_groups_', '', $element);
            } elseif ((int)$element) {
                $frontendUserIds[] = (int)$element;
            }
        }

        if (!empty($frontendGroupIds)) {
            $groupResolver = GeneralUtility::makeInstance(GroupResolver::class);
            $frontendUsersInGroups = $groupResolver->findAllUsersInGroups($frontendGroupIds, 'fe_groups', 'fe_users');
            foreach ($frontendUsersInGroups as $frontendUsers) {
                $frontendUserIds[] = (int)$frontendUsers['uid'];
            }
        }

        return array_unique($frontendUserIds);
    }

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory): void
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    public function injectBackendUriBuilder(BackendUriBuilder $backendUriBuilder): void
    {
        $this->backendUriBuilder = $backendUriBuilder;
    }

}