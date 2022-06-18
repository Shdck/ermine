<?php

namespace Ermine;

trait KeysColumnUsageMapperTrait
{
    /** @var [] */
    protected static $columns = [
        'COLUMN_NAME' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'REFERENCED_TABLE_NAME' => [
            'type' => 'varchar',
            'notnull' => false,
            'default' => null,
        ],
        'REFERENCED_COLUMN_NAME' => [
            'type' => 'varchar',
            'notnull' => false,
            'default' => null,
        ],
    ];

    /** @var string */
    protected static $tableName = 'KEY_COLUMN_USAGE';

    /** @var string */
    protected static $schema = 'information_schema';
}