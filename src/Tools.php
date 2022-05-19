<?php

namespace Ermine;

use Exception;

/**
 * Class Tools
 * @package Ermine
 * @author Mathieu Beneston
 * @todo check for warning in phpStorm
 */
class Tools
{

    /**
     * This is a static class, so it can't be instantiated
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('This class cannot be instantiated.');
    }

    /**
     * @param $variable
     */
    static function dump($variable, $indexOfBacktraceToRead = 0)
    {
        echo PHP_EOL;
        echo(php_sapi_name() != 'cli' ? '<pre class="erminedump">' : '');
        echo debug_backtrace()[$indexOfBacktraceToRead]['file'] . ':' . debug_backtrace()[$indexOfBacktraceToRead]['line'] . PHP_EOL;
        var_dump($variable);
        echo(php_sapi_name() != 'cli' ? "</pre>" : '');
        echo PHP_EOL;
    }

    /**
     * @param Exception $exception
     */
    static function dumpException($exception, $indexOfBacktraceToRead = 0)
    {
        echo '<pre class="erminedump">';
        echo debug_backtrace()[$indexOfBacktraceToRead]['file'] . ':' . debug_backtrace()[$indexOfBacktraceToRead]['line'] . PHP_EOL;
        echo "<b>" . get_class($exception) . ' : ' . $exception->getMessage() . "</b>\n";
        echo "Error in file " . $exception->getFile() . ":" . $exception->getLine() . PHP_EOL;
        foreach ($exception->getTrace() as $trace) {
            if (isset($trace['file']) && isset($trace['line'])) {
                echo "              " . $trace['file'] . ':' . $trace['line'] . PHP_EOL;
            }
        }
        echo "</pre>";
    }

    /**
     * @param $routeKey
     * @param $params
     * @param $base64Encode
     * @param $withDomain
     * @param $language
     * @return array|mixed|string|string[]|null
     * @todo should be rewrite
     */
    public static function getUrl($routeKey, $params=null, $base64Encode=false, $withDomain=false, $language=LANGUAGE) {
        global $arrRoutes;

        if (!isset($arrRoutes[$routeKey])) {
            return null;
        }

        $route = $arrRoutes[$routeKey];

        if (
            !isset($route['url'][$language]) &&
            !isset($route['url']['all'])
        ) {
            return null;
        }

        $url = ($route['url'][$language] ?? $route['url']['all']);

        if ($base64Encode) {
            $url = base64_encode($url);
        }

        if ($withDomain) {
            switch ($language) {
                case 'en':
                    $domain = SH_HTTP_ROOT_EN;
                    break;
                default:
                    $domain = SH_HTTP_ROOT_FR;
            }

            $url = rtrim($domain, '/') . $url;
        }

        if (is_null($params) && isset($route['params'])) {
            preg_match(
                '#^' . ($route['regex'][LANGUAGE] ?? $route['regex']['all']) . '$#',
                tools::getParameters('controller', 'home', INPUT_GET, filterCallback::SYSTEM_STRING),
                $matches
            );
            if (!empty($matches)) {
                foreach ($route['params'] as $paramName => $param) {
                    if (substr($param, 0, 1) == '$') {
                        $url = str_replace(
                            '%' . $paramName . '%',
                            $matches[substr($param, 1)],
                            $url
                        );
                    }
                }
            }
        }

        if (!is_null($params)) {
            foreach ($params as $paramName => $paramValue) {
                $url = str_replace(
                    '%' . $paramName . '%',
                    $paramValue,
                    $url
                );
            }
        }

        return $url;
    }

    static function plural($nb, $singular, $plural)
    {
        return ($nb > 1 ? $plural : $singular);
    }

    /**
     * @param $passwordLength
     * @return string
     * @todo split $alphabet in several and add options (withMajor, withMinor, withNumber, withSpecial)
     */
    static function randomPassword($passwordLength = 8)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890&#@$%*!?';
        $pass = []; //remember to declare $pass as an array
        for ($i = 0; $i < $passwordLength; $i++) {
            $n = rand(0, strlen($alphabet) - 1);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /**
     * @param string $dateSystem
     * @param string $locale
     * @return false|string
     * @todo format date in function of $locale
     */
    static function dateFormated(string $dateSystem, string $locale='fr'): string {
        return date('d/m/Y', strtotime($dateSystem));
    }

    /**
     * @param $number
     * @param string $locale
     * @param int $decimals
     * @return string
     * @todo format number in function of $locale
     */
    static function numberFormated($number, string $locale = 'fr', int $decimals = 2)
    {
        return number_format($number, $decimals, ',', ' ');
    }

    /**
     * @param $price
     * @param $locale
     * @return string
     * @todo format number in function of $locale
     */
    static function priceFormated($price, $locale = 'fr')
    {
        return static::numberFormated($price, $locale) . '€';
    }

}
