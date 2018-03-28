<?php
namespace Psmb\Newsletter\Service\DataSource;

use Neos\Flow\Annotations as Flow;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;

class RepositoryDataSource extends AbstractDataSource
{
    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @var string
     */
    protected static $identifier = 'Repository';

    /**
     * Get subscribers from the repository
     *
     * {@inheritdoc}
     */
    public function getData(array $subscription)
    {
        return $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])->toArray();
    }
}