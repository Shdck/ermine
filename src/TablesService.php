<?php

namespace Ermine;

use Exception;

class TablesService extends ModelMySqlAdapter
{
    use TablesMapperTrait;

    /**
     * @throws Exception
     */
    public function getTableName(): string
    {
        return $this->__get('TABLE_NAME');
    }
}