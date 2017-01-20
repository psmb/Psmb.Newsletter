<?php
namespace Psmb\Newsletter\Domain\Repository;

use Psmb\Newsletter\Domain\Model\Filter;
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

    /**
     * @param Filter $filter
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findAllByFilter($filter)
    {
        $query = $this->createQuery();
        $constraint = [];

        if (!empty($filter->getName())) {
            $constraint[] = $query->like('name', '%' . $filter->getName() . '%');
        }
        if (!empty($filter->getEmail())) {
            $constraint[] = $query->like('email', '%' . $filter->getEmail() . '%');
        }
        if (!empty($filter->getSubscriptions())) {
            $subs = [];

            foreach ($filter->getSubscriptions() as $subscription) {
                $subs[] = $query->like('subscriptions', '%"' . $subscription . '"%');
            }
            $constraint[] = $query->logicalAnd($subs);
        }
        if (empty($constraint)) {
            return $this->findAll();
        }

        return $query->matching($query->logicalAnd(
            $constraint
        ))->execute();
    }
}
