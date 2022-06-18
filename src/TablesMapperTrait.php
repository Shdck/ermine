<?php

namespace Ermine;

/**
 * @property string $columnName
 */
trait TablesMapperTrait
{
    /** @var [] */
    protected static $columns = [
        'TABLE_NAME' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
    ];

    /** @var string */
    protected static $tableName = 'TABLES';

    /** @var string */
    protected static $schema = 'information_schema';
}