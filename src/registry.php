<?php

namespace ermine;

use Exception;

class registry {

    /**
     * @var array
     */
    protected static $registry;

    /**
     * @throws Exception
     */
    public function __construct() {
        throw new Exception('This class cannot be instantiated.');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value) {
        static::$registry[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key) {
        return static::$registry[$key];
    }

}
