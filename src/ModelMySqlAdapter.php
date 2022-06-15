<?php

namespace Ermine;

use Exception;
use PDO;

abstract class ModelMySqlAdapter extends Model
{

    /** @var [] */
    protected static $columns;

    /** @var string */
    protected static $tableName;

    /** @var string */
    protected static $schema;

    /**
     * @param array $data
     * @throws Exception
     */
    public function __construct($data = [])
    {
        // We don't use $this->createSqlConnection(); right now.
        // It will be call only when we need to use it.

        // this class must use a mapper
        if (count(class_uses($this))) {
            throw new Exception('You must use a ModelMapperTrait in your model.');
        }

        parent::__construct($data);
    }

    /**
     * @return PDO|null
     */
    protected static function getSqlConnection()
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

        $server = $config->MySql->server;
        $username = $config->MySql->username;
        $password = $config->MySql->password;
        $database = $config->MySql->database;

        $sqlConnection = new PDO("mysql:host=$server;dbname=$database", $username, $password, array(PDO::ATTR_PERSISTENT => false));
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
            $sqlConnection = static::getSqlConnection();
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
                'Sql request generate a Exception ' . $e->getMessage() . PHP_EOL . 'Request:' . PHP_EOL . $sql,
                $e->getCode(),
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
        $primaryKey = $this->getPrimaryKeys();
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

    public function setData(array $data): Model
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^get(.+)$/', $name, $matches)) {
            $column = strtolower($matches[1]);
            return $this->__get($column);
        }

        if (preg_match('/^set(.+)$/', $name, $matches)) {
            $column = strtolower($matches[1]);
            if (isset($arguments[0])) {
                return $this->__set($column, $arguments[0]);
            }
        }

        throw new Exception($name . " method doesn't exist ");
    }

    /**
     * @throws Exception
     */
    public function __get($name)
    {
        if (isset(static::$columns[$name])) {
            return static::$columns[$name];
        }
        throw new Exception($name . ' is not a column of ' . static::getTableName());
    }

    /**
     * @throws Exception
     */
    public function __set($name, $value)
    {
        if (isset(static::$columns[$name])) {
            static::$columns[$name]['value'] = $value;
            return $this;
        }
        throw new Exception($name . ' is not a column of ' . static::getTableName());
    }

    private function getPrimaryKeys(): array
    {
        return array_filter(static::$columns, function ($column) {
            return $column['primary'] ?? false;
        });
    }

    /**
     * Enregistrement de l'objet
     * @throws Exception
     */
    public function insert()
    {
        $sql = 'insert into ' . static::getTableName() . ' ';
        $sqlColumns = [];
        $sqlValues = [];
        $bind = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            $sqlColumns[] = $columnName;
            $sqlValues[] = ':' . $columnName;
            $bind[':' . $columnName] = $columnStructure['value'];
        }
        $sql .= '(' . implode(', ', $sqlColumns) . ' values (' . implode(', ', $sqlValues) . ')';
        $this->executeSql($sql, $bind, false);

        // We need to set the primary key if it is autoincrement
        $autoIncrementColumn = array_filter(static::$columns, function ($column) {
            return $column['autoincrement'] ?? false;
        });
        if ($autoIncrementColumn) {
            $this->{$autoIncrementColumn[0]} = static::getSqlConnection()->lastInsertId();
        }
    }

    /**
     * Mise à jour de l'objet
     * @throws Exception
     */
    public function update()
    {
        $sql = 'update ' . static::getTableName() . ' set ';
        $sqlSet = [];
        $sqlWhere = [];
        $bind = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            if (!empty($columnStructure['primary'])) {
                $sqlWhere[] = $columnName . ' = :' . $columnName;
            } else {
                $sqlSet[] = $columnName . ' = :' . $columnName;
            }
            $bind[':' . $columnName] = $columnStructure['value'];
        }
        $sql .= implode(', ', $sqlSet) . ' where ' . implode(' and ', $sqlWhere);
        $this->executeSql($sql, $bind, false);
    }

    public static function quote($value)
    {
        $sqlConnection = static::getSqlConnection();
        return $sqlConnection->quote($value);
    }

    /**
     * Suppression de l'objet
     * @throws Exception
     */
    public function delete()
    {
        $sql = 'delete from ' . static::getTableName() . ' ';
        $sqlWhere = [];
        $bind = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            if (!empty($columnStructure['primary'])) {
                $sqlWhere[] = $columnName . ' = :' . $columnName;
                $bind[':' . $columnName] = $columnStructure['value'];
            }
        }
        $sql .= 'where ' . implode(' and ', $sqlWhere);
        $this->executeSql($sql, $bind, false);
    }

    /**
     * @return string
     */
    public static function getTableName(): string
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
    public static function getList(array $filters = [], array $order = [], int $offset = null, int $limit = null): array
    {
        $sqlSelect = [];
        foreach (static::$columns as $columnName => $columnStructure) {
            $sqlSelect[] = '`' . $columnName . '`';
        }
        $sql = 'select ' . implode(', ', $sqlSelect) . ' from ' . static::getTableName();
        $sqlWhere = [];
        $bind = [];
        foreach ($filters as $columnName => $value) {
            // If columnName is an integer, we assume this is an expression (my_colum = 1 by example)
            if (is_integer((string)$columnName)) {
                $sqlWhere[] = $value;
                continue;
            }

            if (!array_key_exists($columnName, static::$columns)) {
                throw new Exception($columnName . ' is not a column of ' . static::getTableName());
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

        return static::executeSql($sql, $bind);
    }

    /**
     * @param array $filters - filter to apply to the request (key => value or expression)
     * @param array $order - order by to apply to the request
     * @return static|null
     * @throws Exception
     */
    public static function getFirst(array $filters = [], array $order = [])
    {
        $list = self::getList($filters, $order, 0, 1);

        return (count($list) ? $list[0] : null);
    }

}