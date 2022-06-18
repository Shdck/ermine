<?php

namespace Ermine;

trait ColumnsMapperTrait
{
    /** @var [] */
    protected static $columns = [
        'TABLE_SCHEMA' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'TABLE_NAME' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'COLUMN_NAME' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'COLUMN_DEFAULT' => [
            'type' => 'longtext',
            'notnull' => false,
            'default' => null,
        ],
        'IS_NULLABLE' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'DATA_TYPE' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'COLUMN_KEY' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
        'EXTRA' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
        ],
    ];

    /** @var string */
    protected static $tableName = 'COLUMNS';

    /** @var string */
    protected static $schema = 'information_schema';
}