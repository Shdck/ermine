<?php

namespace Ermine;

use Exception;
use PDO;

/**
 * @property array $columns
 * @property string $tableName
 * @property string $schema
 * @todo:
 * - Generate information_schema traits
 */
abstract class ModelMySqlAdapter extends Model
{
    use ModelGetterSetterCallerTrait;

    protected $columnValues = [];

    /**
     * @return PDO|null
     */
    protected static function sqlConnection()
    {
        $config = Registry::get('config');
        $sqlConnection = Registry::get($config->MySql->registryKey);
        if (is_null($sqlConnection)) {
            static::createSqlConnection();
        }
        return Registry::get($config->MySql->registryKey);
    }

    private static function createSqlConnection()
    {
        $config = Registry::get('config');

        $host = $config->MySql->host;
        $username = $config->MySql->username;
        $password = $config->MySql->password;
        $database = $config->MySql->database;

        $sqlConnection = new PDO("mysql:host=$host;dbname=$database", $username, $password, array(PDO::ATTR_PERSISTENT => false));
        $sqlConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Registry::set($config->MySql->registryKey, $sqlConnection);
    }

    /**
     * @param string $sql
     * @param []|null $bind
     * @param bool $instantiate
     * @param bool $debug
     * @return static[]
     * @throws Exception
     */
    protected static function executeSql(string $sql, $bind = null, bool $instantiate = true, bool $debug = false): array
    {
        if ($debug) {
            Tools::dump($sql);
            Tools::dump($bind);
        }

        $list = [];
        try {
            $sqlConnection = static::sqlConnection();
            $statement = $sqlConnection->prepare($sql);
            $statement->execute($bind);

            // We don't need to instantiate the model (maybe insert or update request)
            if (!$instantiate) {
                return [];
            }

            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            $class = get_called_class();

            // We need to instantiate all rows
            foreach ($rows as $row) {
                $list[] = new $class($row);
            }
        } catch (Exception $e) {
            throw new Exception(
                'Sql request generate a Exception ' . $e->getMessage() . PHP_EOL . 'Request:' . $sql . PHP_EOL . 'Bind:' . var_export($bind, true),
                0,
                $e
            );
        }

        return $list;
    }

    /**
     * @throws Exception
     */
    public function save()
    {
        $primaryKey = $this->primaryKeys();
        $isPrimaryKeysSet = true;
        foreach ($primaryKey as $key) {
            if (is_null($this->$key)) {
                $isPrimaryKeysSet = false;
                break;
            }
        }
        if ($isPrimaryKeysSet) {
            $this->update();
            return;
        }

        $this->insert();
    }

    protected function initData(array $data): Model
    {
        foreach (static::$columns as $columnName => $columnStructure) {
            $this->$columnName = $columnStructure['default'];
        }
        foreach ($data as $columnName => $value) {
            $this->$columnName = $value;
        }
        return $this;
    }

    private function primaryKeys(): array
    {
        return array_keys(
            array_filter(static::$columns, function ($column) {
                return $column['primary'] ?? false;
            })
        );
    }

    /**
     * Enregistrement de l'objet
     * @throws Exception
     */
    public function insert()
    {
        $sql = 'insert into ' . static::tableName() . ' ';
        $sqlColumns = [];
        $sqlValues = [];
        $bind = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            $sqlColumns[] = $columnName;
            $sqlValues[] = ':' . $columnName;
            $bind[':' . $columnName] = $this->__get($columnName);
        }
        $sql .= '(' . implode(', ', $sqlColumns) . ') values (' . implode(', ', $sqlValues) . ')';
        $this->executeSql($sql, $bind, false);

        // We need to set the primary key if it is autoincrement
        $autoIncrementColumn = array_keys(
            array_filter(static::$columns, function ($column) {
                return $column['autoincrement'] ?? false;
            })
        );
        if ($autoIncrementColumn) {
            $this->{$autoIncrementColumn[0]} = static::sqlConnection()->lastInsertId();
        }
    }

    /**
     * Mise à jour de l'objet
     * @throws Exception
     */
    public function update()
    {
        $sql = 'update ' . static::tableName() . ' set ';
        $sqlSet = [];
        $sqlWhere = [];
        $bind = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            if (!empty($columnStructure['primary'])) {
                $sqlWhere[] = $columnName . ' = :' . $columnName;
            } else {
                $sqlSet[] = $columnName . ' = :' . $columnName;
            }
            $bind[':' . $columnName] = $this->__get($columnName);
        }
        $sql .= implode(', ', $sqlSet) . ' where ' . implode(' and ', $sqlWhere);
        $this->executeSql($sql, $bind, false);
    }

    public static function quote($value)
    {
        $sqlConnection = static::sqlConnection();
        return $sqlConnection->quote($value);
    }

    /**
     * Suppression de l'objet
     * @throws Exception
     */
    public function delete()
    {
        $sql = 'delete from ' . static::tableName() . ' ';
        $sqlWhere = [];
        $bind = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            if (!empty($columnStructure['primary'])) {
                $sqlWhere[] = $columnName . ' = :' . $columnName;
                $bind[':' . $columnName] = $this->__get($columnName);
            }
        }
        $sql .= 'where ' . implode(' and ', $sqlWhere);
        $this->executeSql($sql, $bind, false);
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return static::$schema . '.' . static::$tableName;
    }

    /**
     * @param array $filters - filter to apply to the request (key => value or expression)
     * @param array $order - order by to apply to the request
     * @param integer|null $offset - offset to apply to the limit
     * @param integer|null $limit - limit to apply to the request
     * @return static[] Result of the request
     * @throws Exception
     */
    public static function list(array $filters = [], array $order = [], int $offset = null, int $limit = null, bool $debug = false): array
    {
        $sqlSelect = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            $sqlSelect[] = '`' . $columnName . '`';
        }
        $sql = 'select ' . implode(', ', $sqlSelect) . ' from ' . static::tableName();
        $sqlWhere = [];
        $bind = [];
        foreach ($filters as $columnName => $value) {
            // If columnName is an integer, we assume this is an expression (my_colum = 1 by example)
            if (is_integer($columnName)) {
                $sqlWhere[] = $value;
                continue;
            }

            if (is_null($value)) {
                $sqlWhere[] = $columnName . ' is null';
            } elseif (is_bool($value)) {
                $sqlWhere[] = $columnName . ' = ' . ($value ? 'true' : 'false');
            } elseif (is_array($value)) {
                if (count($value)) {
                    $sqlWhere[] = $columnName .
                        ' in (' .
                        implode(
                            ', ',
                            array_map(
                                array('self', 'quote'),
                                $value
                            )
                        ) .
                        ')';
                }
            } else {
                $sqlWhere[] = $columnName . ' = :' . $columnName;
                $bind[':' . $columnName] = $value;
            }
        }

        if ($sqlWhere) {
            $sql .= ' where ' . implode(' and ', $sqlWhere);
        }

        if ($order) {
            $sql .= ' order by ' . implode(', ', $order);
        }

        if (!is_null($limit)) {
            $sql .= ' limit';
            if (!is_null($offset)) {
                $sql .= ' ' . $offset . ',';
            }
            $sql .= ' ' . $limit;
        }

        return static::executeSql($sql, $bind, true, $debug);
    }

    /**
     * @param array $filters - filter to apply to the request (key => value or expression)
     * @param array $order - order by to apply to the request
     * @return static|null
     * @throws Exception
     */
    public static function first(array $filters = [], array $order = [], bool $debug = false)
    {
        $list = self::list($filters, $order, 0, 1, $debug);

        return (count($list) ? $list[0] : null);
    }

}