<?php
namespace Psmb\Newsletter\Domain\Repository;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class SubscriberRepository extends Repository
{
    /**
     * @param $subscriptionId
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findBySubscriptionId($subscriptionId)
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->like('subscriptions', '%"' . $subscriptionId . '"%')
        )->execute();
    }
}
