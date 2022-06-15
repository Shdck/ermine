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
            'value' => '',
        ],
        'REFERENCED_TABLE_NAME' => [
            'type' => 'varchar',
            'notnull' => false,
            'default' => null,
            'value' => null,
        ],
        'REFERENCED_COLUMN_NAME' => [
            'type' => 'varchar',
            'notnull' => false,
            'default' => null,
            'value' => null,
        ],
    ];

    /** @var string */
    protected static $tableName = 'cart';

    /** @var string */
    protected static $schema = 'information_schema';

    public function getColumnName(): string
    {
        return static::$columns['COLUMN_NAME']['value'];
    }

    public function getForeign(): array
    {
        return [
            'table' => static::$columns['REFERENCED_TABLE_NAME']['value'],
            'column' => static::$columns['REFERENCED_COLUMN_NAME']['value'],
        ];
    }
}