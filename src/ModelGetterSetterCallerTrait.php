<?php

namespace Ermine;

use Exception;

trait ModelGetterSetterCallerTrait
{

    /**
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^get(.+)$/', $name, $matches)) {
            $column = $matches[1];
            return $this->__get($column);
        }

        if (preg_match('/^set(.+)$/', $name, $matches)) {
            $column = $matches[1];
            if (isset($arguments[0])) {
                return $this->__set($column, $arguments[0]);
            }
        }

        throw new Exception($name . " method doesn't exist in " . get_called_class());
    }

    /**
     * @throws Exception
     */
    public function __get($name)
    {
        if (isset(static::$columns[$name])) {
            $columValue = $this->columnValues[$name];
            return $columValue ?? static::$columns[$name]['default'];
        }
        throw new Exception($name . ' is not a column of ' . get_called_class());
    }

    /**
     * @throws Exception
     */
    public function __set($name, $value)
    {
        if (isset(static::$columns[$name])) {
            $this->columnValues[$name] = $value;
            return $this;
        }
        throw new Exception($name . ' is not a column of ' . get_called_class());
    }
}