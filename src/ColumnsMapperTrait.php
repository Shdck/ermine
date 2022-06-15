<?php

namespace Ermine;

use Exception;

trait ColumnsMapperTrait
{
    /** @var [] */
    protected static $columns = [
        'COLUMN_NAME' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
            'value' => '',
        ],
        'COLUMN_DEFAULT' => [
            'type' => 'longtext',
            'notnull' => false,
            'default' => null,
            'value' => null,
        ],
        'IS_NULLABLE' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
            'value' => '',
        ],
        'DATA_TYPE' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
            'value' => '',
        ],
        'COLUMN_KEY' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
            'value' => '',
        ],
        'EXTRA' => [
            'type' => 'varchar',
            'notnull' => true,
            'default' => '',
            'value' => '',
        ],
    ];

    /** @var string */
    protected static $tableName = 'COLUMNS';

    /** @var string */
    protected static $schema = 'information_schema';

    public function getColumnName(): string
    {
        return static::$columns['COLUMN_NAME']['value'];
    }

    public function getColumnDefault(): string
    {
        return static::$columns['COLUMN_DEFAULT']['value'];
    }

    public function isNullable(): bool
    {
        return (static::$columns['IS_NULLABLE']['value'] === 'YES');
    }

    public function getDataType(): string
    {
        return static::$columns['DATA_TYPE']['value'];
    }

    public function isPrimary(): bool
    {
        return (static::$columns['COLUMN_KEY']['value'] === 'PRI');
    }

    public function isAutoIncrement(): bool
    {
        return (static::$columns['EXTRA']['value'] === 'auto_increment');
    }

    /**
     * @throws Exception
     */
    public function getForeignKeys(): array
    {
        $keysColumnUsage = KeysColumnUsageService::getFirst(
            [
                'SCHEMA_NAME' => static::$schema,
                'TABLE_NAME' => static::$tableName,
                'COLUMN_NAME' => $this->getColumnName(),
            ]
        );
        return $keysColumnUsage->getForeign();
    }
}