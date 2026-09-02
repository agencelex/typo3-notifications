<?php declare(strict_types=1);

namespace Lex\Notifications\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class NotifiableFrontendUserRepository extends Repository
{
    public function findByUids(array $uids): array|QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $constraints = [
            $query->in('uid', $uids),
        ];

        $query->matching($query->logicalAnd(...$constraints));

        return $query->execute();
    }
}