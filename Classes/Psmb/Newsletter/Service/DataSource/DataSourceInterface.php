<?php
namespace Psmb\Newsletter\Service\DataSource;

/**
 * Data source interface for providing generic data
 *
 * This is used in the user interface to generate dynamic option lists.
 *
 * @api
 */
interface DataSourceInterface
{
    /**
     * @return string The identifier of the data source
     * @api
     */
    public static function getIdentifier();

    /**
     * Get data
     *
     * The return value must be JSON serializable data structure.
     *
     * @param array $subscription Subscription data
     * @return mixed data
     * @api
     */
    public function getData(array $subscription);
}
