<?php
namespace Psmb\Newsletter\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class SubscriberRepository extends Repository
{
    /**
     * @param $subscriptionId
     * @return \Neos\Flow\Persistence\QueryResultInterface
     */
    public function findBySubscriptionId($subscriptionId)
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->like('subscriptions', '%"' . $subscriptionId . '"%')
        )->execute();
    }
}
