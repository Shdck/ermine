<?php

namespace Ermine;

use Exception;

class ColumnsService extends ModelMySqlAdapter
{
    use ColumnsMapperTrait;

    /**
     * @throws Exception
     */
    public function getTableSchema(): string
    {
        return $this->__get('TABLE_SCHEMA');
    }

    /**
     * @throws Exception
     */
    public function getTableName(): string
    {
        return $this->__get('TABLE_NAME');
    }

    /**
     * @throws Exception
     */
    public function getColumnName(): string
    {
        return $this->__get('COLUMN_NAME');
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getColumnDefault()
    {
        return $this->__get('COLUMN_DEFAULT');
    }

    /**
     * @throws Exception
     */
    public function isNullable(): bool
    {
        return ($this->__get('IS_NULLABLE') === 'YES');
    }

    /**
     * @throws Exception
     */
    public function getDataType(): string
    {
        return $this->__get('DATA_TYPE');
    }

    /**
     * @throws Exception
     */
    public function isPrimary(): bool
    {
        return ($this->__get('COLUMN_KEY') === 'PRI');
    }

    /**
     * @throws Exception
     */
    public function isAutoIncrement(): bool
    {
        return ($this->__get('EXTRA') === 'auto_increment');
    }

    /**
     * @throws Exception
     */
    public function getForeignKeys(): array
    {
        $keysColumnUsage = KeysColumnUsageService::first(
            [
                'TABLE_SCHEMA' => $this->getTableSchema(),
                'TABLE_NAME' => $this->getTableName(),
                'COLUMN_NAME' => $this->getColumnName(),
                'REFERENCED_TABLE_NAME is not null',
                'REFERENCED_COLUMN_NAME is not null',
            ]
        );
        if ($keysColumnUsage === null) {
            return [];
        }
        return $keysColumnUsage->getForeign();
    }
}