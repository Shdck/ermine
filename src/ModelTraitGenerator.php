<?php

namespace Ermine;

use Exception;

class ModelTraitGenerator
{
    /** @var array $tablesToSkip - List of tables excluded from the generation */
    private $tablesToSkip = [];

    /** @var string $schema - Schema on which we will make the generation */
    private $schema;

    /** @var string $namespace - Namespace of the generated trait */
    private $namespace;

    /** @var string $destinationDirectory - Directory where the traits will be written */
    private $destinationDirectory;

    public function __construct($namespace, $destinationDirectory)
    {
        $config = Registry::get('config');
        $this->schema = $config->MySql->database;
        $this->namespace = $namespace;
        $this->destinationDirectory = ltrim($destinationDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * @throws Exception
     */
    public function generateTraits()
    {
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->generateTrait($table);
        }
    }

    /**
     * @throws Exception
     */
    private function generateTrait(string $table)
    {
        if (in_array($table, $this->tablesToSkip)) {
            return;
        }
        $attributes = [];
        $getters = [];
        $setters = [];
        $columnsDescription = [];
        $columns = $this->getColumns($table);
        foreach ($columns as $column) {
            $attributes[] = $this->generateAttribute($column);
            $getters[] = $this->generateGetter($column);
            $setters[] = $this->generateSetter($column);
            $columnsDescription[$column->getColumnName()] = $this->generateColumnStructure($column);
        }

        $implodedAttributes = implode(PHP_EOL, $attributes);
        $implodedGetters = implode(PHP_EOL, $getters);
        $implodedSetters = implode(PHP_EOL, $setters);
        $implodedColumnsDescription = preg_replace(
            '/^array\((.*)\)$/',
            '[$1]',
            var_export($columnsDescription, true)
        );
        $traitName = $this->generateTraitName($table);
        $phpTrait = <<<PHP
<?php

namespace {$this->namespace};

/**
 * This trait is generated automatically by Ermine. Changes made manually may be overwritten.
 * {$implodedAttributes}
 * {$implodedGetters}
 * {$implodedSetters}
 */
trait {$traitName}
{
    /** @var [] */
    protected \$columns = {$implodedColumnsDescription};

    /** @var string */
    protected \$tableName = '{$table}';

    /** @var string */
    protected \$schema = '{$this->schema}';
}
PHP;

        file_put_contents(
            $this->destinationDirectory . '/' . $traitName . '.php',
            $phpTrait
        );
    }

    /**
     * @param ColumnsService $column
     * @return string
     */
    private function generateAttribute(ColumnsService $column): string
    {
        return ' * @var ' . $this->getPhpDocType($column) . ' $' . $column->getColumnName();
    }

    /**
     * @param ColumnsService $column
     * @return string
     */
    private function generateGetter(ColumnsService $column): string
    {
        return ' * @method ' . $this->getPhpDocType($column) . ' get' . $column->getColumnName() . '()';
    }

    private function generateSetter(ColumnsService $column): string
    {
        return ' * @method ' . $this->getPhpDocType($column) . ' set' . $column->getColumnName() .
            '(' . $this->getParameterType($column) . ' $' . $column->getColumnName() . ')';
    }

    /**
     * @throws Exception
     */
    private function generateColumnStructure(ColumnsService $column): array
    {
        $structure = [
            'type' => $column->getDataType(),
            'notnull' => (!$column->isNullable()),
            'default' => $column->getColumnDefault(),
        ];
        if ($column->isPrimary()) {
            $structure['primary'] = true;
        }
        if ($column->isAutoIncrement()) {
            $structure['autoincrement'] = true;
        }
        $foreign = $column->getForeignKeys();
        if (count($foreign) > 0) {
            $structure['foreign'] = $foreign;
        }
        $structure['value'] = $column->getColumnDefault();
        return $structure;
    }

    private function generateTraitName(string $tableName): string
    {
        $explodedTableName = explode('_', $tableName);
        foreach ($explodedTableName as &$word) {
            $word = ucfirst($word);
        }
        return implode('', $explodedTableName) . 'MapperTrait';
    }

    private function getPhpDocType(ColumnsService $column): string
    {
        $tabSqlTypes = [
            'bit' => 'int',
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'year' => 'int',
            'int' => 'int',
            'timestamp' => 'int',
            'bool' => 'bool',
            'tinyint(1)' => 'bool',
            'dec' => 'float',
            'float' => 'float',
            'double' => 'float',
            'date' => 'string',
            'time' => 'string',
            'char' => 'string',
            'varchar' => 'string',
            'text' => 'string',
            'enum' => 'string',
            'set' => 'string',
            'binary' => 'string',
            'varbinary' => 'string',
            'blob' => 'string',
            'tinytext' => 'string',
            'tinyblob' => 'string',
            'mediumtext' => 'string',
            'mediumblob' => 'string',
            'longtext' => 'string',
            'longblob' => 'string',
        ];

        $sqlDataType = strtolower($column->getDataType());
        return $tabSqlTypes[$sqlDataType] ?? 'mixed';
    }

    private function getParameterType(ColumnsService $column): string
    {
        $phpType = $this->getPhpDocType($column);
        return ($phpType !== 'mixed' ? $phpType : '');
    }

    /**
     * @throws Exception
     */
    private function getTables(): array
    {
        $tablesToSkipQuoted = array_map(function ($table) {
            return TablesService::quote($table);
        }, $this->tablesToSkip);
        return TablesService::getList(
            [
                'TABLE_SCHEMA' => $this->schema,
                'TABLE_NAME not in (' . implode(',', $tablesToSkipQuoted) . ')',
            ],
            ['TABLE_NAME']
        );
    }

    /**
     * @return ColumnsService[]
     * @throws Exception
     */
    private function getColumns(string $table): array
    {
        return ColumnsService::getList(
            [
                'TABLE_SCHEMA' => $this->schema,
                'TABLE_NAME' => $table,
            ],
            ['ORDINAL_POSITION']
        );
    }

    /**
     * @return array
     */
    public function getTablesToSkip(): array
    {
        return $this->tablesToSkip;
    }

    /**
     * @param array $tablesToSkip
     * @return ModelTraitGenerator
     */
    public function setTablesToSkip(array $tablesToSkip): ModelTraitGenerator
    {
        $this->tablesToSkip = $tablesToSkip;
        return $this;
    }

}
