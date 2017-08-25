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
     * @var array
     */
    protected $defaultOrderings = array(
        'name' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING
    );

    /**
     * @param string $filter
     * @return \TYPO3\Flow\Persistence\QueryResultInterface
     */
    public function findAllByFilter($filter)
    {
        $query = $this->createQuery();

        return $query->matching(
            $query->like('subscriptions', '%"' . $filter . '"%')
        )->execute();
    }
}
