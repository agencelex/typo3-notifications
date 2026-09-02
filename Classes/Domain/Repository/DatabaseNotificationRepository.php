<?php declare(strict_types=1);

namespace Lex\Notifications\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class DatabaseNotificationRepository extends Repository
{
    public function findByNotifiable(int $notifiableUid): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = [
            $query->equals('notifiable_id', $notifiableUid)
        ];

        $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);

        $query->matching($query->logicalAnd(...$constraints));

        return $query->execute();
    }

    public function markAllAsReadForNotifiable(int $notifiableUid): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->update($this->tableName)
            ->set('read_at', time())
            ->where($queryBuilder->expr()->eq('notifiable_id', $queryBuilder->createNamedParameter($notifiableUid, Connection::PARAM_INT)))
            ->executeStatement();
    }
    public function removeAllForNotifiable(int $notificationId): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->delete($this->tableName)
            ->where($queryBuilder->expr()->eq('notifiable_id', $queryBuilder->createNamedParameter($notificationId, Connection::PARAM_INT)))
            ->executeStatement();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);
    }

    protected string $tableName = 'tx_lexnotifications_domain_model_notification';
}