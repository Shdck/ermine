<?php

namespace Ermine;

trait TablesMapperTrait
{
    /** @var [] */
    protected static $columns = [
        'TABLE_NAME' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
            'value' => '',
        ],
    ];

    /** @var string */
    protected static $tableName = 'TABLES';

    /** @var string */
    protected static $schema = 'information_schema';

    public function getTableName()
    {
        return static::$columns['TABLE_NAME']['value'];
    }
}