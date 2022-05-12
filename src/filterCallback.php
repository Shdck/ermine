<?php

namespace Ermine;

/** @todo class à revoir (type des arguments et les retours) */
class filterCallback {
    
    const BASIC_STRING = 'basicString';
    const SYSTEM_STRING = 'systemString';

    /**
     * @param string $value valeur à tester
     * @return string
     */
    public static function basicString($value): string {
        return (string)$value;
    }

    /**
     * @param string $value valeur à tester
     * @return bool|string
     */
    public static function systemString(string $value): string {
        
        if (preg_match('#^[a-zA-Z0-9_/-]*$#', $value)) {
            return $value;
        }
        
        return false;
    }
}
