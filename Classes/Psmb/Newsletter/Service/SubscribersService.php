<?php
namespace Psmb\Newsletter\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Psmb\Newsletter\Service\DataSource\DataSourceInterface;

/**
 * @Flow\Scope("singleton")
 */
class SubscribersService {

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Get subscribers data
     *
     * @param array $subscription
     * @return array Subscribers data
     */
    public function getSubscribers($subscription)
    {
        $dataSources = static::getDataSources($this->objectManager);
        // throw new \Exception(print_r($dataSources, 1));
        $dataSourceIdentifier = $subscription['dataSourceIdentifier'] ?? 'Repository';

        if (!isset($dataSources[$dataSourceIdentifier])) {
            throw new \Exception(sprintf('Data source with identifier "%s" does not exist.', $dataSourceIdentifier), 1414082186);
        }

        /** @var $dataSource DataSourceInterface */
        $dataSource = new $dataSources[$dataSourceIdentifier];

        return $dataSource->getData($subscription);
    }

    /**
     * Get available data source implementations
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Data source class names indexed by identifier
     * @Flow\CompileStatic
     */
    public static function getDataSources($objectManager)
    {
        $reflectionService = $objectManager->get(ReflectionService::class);

        $dataSources = array();
        $dataSourceClassNames = $reflectionService->getAllImplementationClassNamesForInterface(DataSourceInterface::class);
        /** @var $dataSourceClassName DataSourceInterface */
        foreach ($dataSourceClassNames as $dataSourceClassName) {
            $identifier = $dataSourceClassName::getIdentifier();
            if (isset($dataSources[$identifier])) {
                throw new \Exception(sprintf('Data source with identifier "%s" is already defined in class %s.', $identifier, $dataSourceClassName), 14140348185);
            }
            $dataSources[$identifier] = $dataSourceClassName;
        }

        return $dataSources;
    }
}