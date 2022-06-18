<?php

namespace Ermine;

use Exception;

class KeysColumnUsageService extends ModelMySqlAdapter
{
    use KeysColumnUsageMapperTrait;

    /**
     * @throws Exception
     */
    public function getForeign(): array
    {
        return [
            'table' => $this->__get('REFERENCED_TABLE_NAME'),
            'column' => $this->__get('REFERENCED_COLUMN_NAME'),
        ];
    }
}