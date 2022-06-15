<?php

namespace Ermine;

use Exception;

class Registry
{

    /**
     * @var array
     */
    protected static $registry;

    /**
     * This is a static class, so it can't be instantiated
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
    public static function set(string $key, $value) {
        static::$registry[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get(string $key) {
        return static::$registry[$key] ?? null;
    }

}
