<?php
namespace Psmb\Newsletter\Service\DataSource;

/**
 * Data source interface for getting data.
 *
 * @api
 */
abstract class AbstractDataSource implements DataSourceInterface
{

    /**
     * The identifier of the operation
     *
     * @var string
     * @api
     */
    protected static $identifier = null;

    /**
     * @return string the short name of the operation
     * @api
     * @throws \Exception
     */
    public static function getIdentifier()
    {
        if (!is_string(static::$identifier)) {
            throw new \Exception('Identifier in class ' . __CLASS__ . ' is empty.', 1414091236);
        }

        return static::$identifier;
    }
}
