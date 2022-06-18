<?php

namespace Ermine;

use Exception;

class ModelTraitGenerator
{
    const SQL_TO_PHP_TYPE_MAP = [
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

    /** @var array $tablesToSkip - List of tables excluded from the generation */
    private $tablesToSkip = [];

    /** @var array $columnsToSkip - List of columns excluded from the generation */
    private $columnsToSkip = [];

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
        $this->destinationDirectory = rtrim($destinationDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * @throws Exception
     */
    public function generateTraits()
    {
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $this->generateTrait($table->getTableName());
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
        $implodedColumnsDescription = str_replace(
            [
                'array (',
                ')',
                ' => ' . PHP_EOL . '  [',
                '  ',
                PHP_EOL,
            ],
            [
                '[',
                ']',
                ' => [',
                '    ',
                PHP_EOL . '    ',
            ],
            var_export($columnsDescription, true)
        );
        $traitName = $this->generateTraitName($table);
        $phpTrait = <<<PHP
<?php

namespace {$this->namespace};

/**
 * This trait is generated automatically by Ermine. Changes made manually may be overwritten.
{$implodedAttributes}
{$implodedGetters}
{$implodedSetters}
 */
trait {$traitName}
{
    /** @var [] */
    protected static \$columns = {$implodedColumnsDescription};

    /** @var string */
    protected static \$tableName = '{$table}';

    /** @var string */
    protected static \$schema = '{$this->schema}';
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
     * @throws Exception
     */
    private function generateAttribute(ColumnsService $column): string
    {
        return ' * @property ' . $this->getPhpDocType($column) . ' $' . $column->getColumnName();
    }

    /**
     * @param ColumnsService $column
     * @return string
     * @throws Exception
     */
    private function generateGetter(ColumnsService $column): string
    {
        return ' * @method ' . $this->getPhpDocType($column) . ' get' . $column->getColumnName() . '()';
    }

    /**
     * @throws Exception
     */
    private function generateSetter(ColumnsService $column): string
    {
        return ' * @method self set' . $column->getColumnName() .
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

    /**
     * @throws Exception
     */
    private function getPhpDocType(ColumnsService $column): string
    {
        $sqlDataType = strtolower($column->getDataType());
        return static::SQL_TO_PHP_TYPE_MAP[$sqlDataType] ?? 'mixed';
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
        $query = ['TABLE_SCHEMA' => $this->schema];
        if (count($tablesToSkipQuoted) > 0) {
            $query[] = 'TABLE_NAME not in (' . implode(',', $tablesToSkipQuoted) . ')';
        }
        return TablesService::list(
            $query,
            ['TABLE_NAME']
        );
    }

    /**
     * @return ColumnsService[]
     * @throws Exception
     */
    private function getColumns(string $table): array
    {
        $columnsToSkipQuoted = array_map(function ($column) {
            return ColumnsService::quote($column);
        }, $this->columnsToSkip);
        $query = [
            'TABLE_SCHEMA' => $this->schema,
            'TABLE_NAME' => $table,
        ];
        if (count($columnsToSkipQuoted) > 0) {
            $query[] = 'COLUMN_NAME not in (' . implode(',', $columnsToSkipQuoted) . ')';
        }
        return ColumnsService::list(
            $query,
            ['ORDINAL_POSITION']
        );
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

    /**
     * @param array $columnsToSkip
     * @return ModelTraitGenerator
     */
    public function setColumnsToSkip(array $columnsToSkip): ModelTraitGenerator
    {
        $this->columnsToSkip = $columnsToSkip;
        return $this;
    }

}
