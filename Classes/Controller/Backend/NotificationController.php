<?php declare(strict_types=1);

namespace Lex\Notifications\Controller\Backend;

use DateTime;
use Lex\Notifications\Domain\Model\Message;
use Lex\Notifications\Domain\Repository\MessageRepository;
use Lex\Notifications\Domain\Repository\NotifiableFrontendUserRepository;
use Lex\Notifications\Extension;
use Lex\Notifications\Notification\BackendUserSentMessageToFrontendUser;
use Lex\Notifications\NotificationDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Http\AllowedMethodsTrait;
use TYPO3\CMS\Core\Http\Error\MethodNotAllowedException;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class NotificationController extends AbstractModuleController
{
    use AllowedMethodsTrait;

    public function __construct(
        protected readonly MessageRepository $messageRepository,
        protected readonly NotifiableFrontendUserRepository $notifiableFrontendUserRepository,
        protected readonly PersistenceManagerInterface $persistenceManager,
    )
    {}

    protected function initializeListAction(): void
    {
        $this->moduleTemplate
            ->setModuleId(Extension::extensionKeyCamelCase() . 'List')
            // Browser title
            ->setTitle($this->translate('mlang_tabs_tab'), '');

        $this->addButtonsForList();
    }

    public function listAction(int $currentPage = 1): ResponseInterface
    {
        $moduleData = $this->request->getAttribute('moduleData');

        $messages = $this->messageRepository->findAll();

        $paginator = new QueryResultPaginator($messages, $currentPage, 50);
        $pagination = new SimplePagination($paginator);

        $this->moduleTemplate->assignMultiple([
            'paginator' => $paginator,
            'pagination' => $pagination,
        ]);

        return $this->moduleTemplate->renderResponse('Backend/Notification/List');
    }

    public function createAction(): ResponseInterface
    {

        return $this->moduleTemplate->renderResponse('Backend/Notification/Create');
    }

    public function storeAction(): ResponseInterface
    {
        return $this->redirect('list');
    }

    /**
     * @throws MethodNotAllowedException
     */
    protected function initializeSendAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    public function sendAction(int $messageUid): ResponseInterface
    {
        /** @var Message $message */
        $message = $this->messageRepository->findByUid($messageUid);

        if(!$message) {
            $this->addFlashNotification(
                $this->translate('notification.sent.failure.not-found.message.body'),
                $this->translate('notification.sent.failure.not-found.message.title'),
                ContextualFeedbackSeverity::ERROR
            );
        } else {

            $targetUserIds = array_diff(
                $this->resolveFrontendUserIds($message->getReceivers()),
                $this->resolveFrontendUserIds($message->getExcludedRecipients())
            );

            $notifiables = $this->notifiableFrontendUserRepository->findByUids($targetUserIds);

            $notification = GeneralUtility::makeInstance(
                BackendUserSentMessageToFrontendUser::class,
                $message->getSubject(),
                $message->getMessage(),
                $message->getLevel(),
                $message->getLink(),
                GeneralUtility::trimExplode(',', $message->getChannels(), true)
            );

            GeneralUtility::makeInstance(NotificationDispatcherInterface::class)->send($notifiables->toArray(), $notification);

            $message->setSentAt(new DateTime());
            $this->messageRepository->update($message);
            $this->persistenceManager->persistAll();

            $this->addFlashNotification(
                $this->translate('notification.sent.success.message.body'),
                $this->translate('notification.sent.success.message.title'),
            );
        }

        return $this->redirect('list');
    }

    /**
     * @throws MethodNotAllowedException
     */
    protected function initializeResendAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    public function resendAction(int $messageUid): ResponseInterface
    {
        return $this->redirect('list');
    }

    public static function getModuleName(): string
    {
        return 'notification';
    }

    protected function addButtonsForList(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $createButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-document-add', IconSize::SMALL))
            ->setTitle($this->translate('btn.create.notification.label'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'id' => $this->pageUid,
                'edit' => ['tx_lexnotifications_domain_model_message' => [$this->pageUid => 'new']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $buttonBar->addButton($createButton);

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_notifications')
            ->setArguments(['action' => 'list'])
            ->setDisplayName($this->translate('mlang_tabs_tab'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }
}