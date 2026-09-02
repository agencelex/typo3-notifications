<?php declare(strict_types=1);

namespace Lex\Notifications\Channel;

use Lex\Notifications\Domain\Model\DatabaseNotification;
use Lex\Notifications\Notification;
use TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class DatabaseChannel implements ChannelInterface
{
    public function __construct(
        protected PersistenceManager $persistenceManager
    ){}

    public function send(object $notifiable, Notification $notification): void
    {
        $notificationModel = (new DatabaseNotification())
            ->setType($notification->getType())
            ->setNotifiableId($notifiable->getUid())
            ->setNotifiableType(get_class($notifiable))
            ->setLevel($notification->getLevel())
            ->setDataFromArray($this->getData($notifiable, $notification))
            ->setReadAt(null);

        // Twist to set the language to 'All'
        $notificationModel->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, -1);

        $this->persistenceManager->add($notificationModel);
        $this->persistenceManager->persistAll();
    }

    protected function getData(object $notifiable, Notification $notification): array
    {
        if (method_exists($notification, 'toDatabase')) {
            return $notification->toDatabase($notifiable);
        }

        throw new NotImplementedMethodException(sprintf("Notification class %s must implement 'toDatabase' to be able to notify via database", $notification->getType()));
    }
}