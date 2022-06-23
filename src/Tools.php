<?php

namespace Ermine;

use Exception;
use Throwable;

/**
 * Class Tools
 * @package Ermine
 * @author Mathieu Beneston
 * @todo check for warning in phpStorm
 */
class Tools
{

    const METHOD_VARDUMP = 'vardump';
    const METHOD_ECHO = 'echo';

    /**
     * @var bool $isStyleDisplayed
     */
    protected static $isStyleDisplayed = false;

    /**
     * This is a static class, so it can't be instantiated
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('This class cannot be instantiated.');
    }

    /**
     * @param mixed $variable
     * @param int $indexOfBacktraceToRead
     * @param null $method
     * @param string $classname
     */
    static function dump($variable, int $indexOfBacktraceToRead = 0, $method = null, string $classname = '')
    {
        if (empty($method)) {
            $method = static::METHOD_VARDUMP;
        }

        static::displayDumpStyle();

        echo PHP_EOL;
        echo(php_sapi_name() != 'cli' ? '<pre class="erminedump' . ($classname ? ' ' . $classname : '') . '">' : '');
        echo(php_sapi_name() != 'cli' ? '<b>' : '');
        echo debug_backtrace()[$indexOfBacktraceToRead]['file'] . ':' . debug_backtrace()[$indexOfBacktraceToRead]['line'] . PHP_EOL;
        echo(php_sapi_name() != 'cli' ? '</b>' : '');
        switch ($method) {
            case static::METHOD_ECHO:
                echo($variable);
                break;
            case static::METHOD_VARDUMP:
            default:
                var_dump($variable);
                break;
        }
        echo(php_sapi_name() != 'cli' ? "</pre>" : '');
        echo PHP_EOL;
    }

    /**
     * @param Exception $exception
     * @param int $indexOfBacktraceToRead
     */
    static function dumpException(Throwable $exception, int $indexOfBacktraceToRead = 0)
    {
        $messageToDump = (php_sapi_name() != 'cli' ? '<b>' : '') .
            get_class($exception) . ' : ' . $exception->getMessage() . PHP_EOL .
            (php_sapi_name() != 'cli' ? '</b>' : '') .
            "Error in file " . $exception->getFile() . ":" . $exception->getLine() . PHP_EOL;
        foreach ($exception->getTrace() as $trace) {
            if (isset($trace['file']) && isset($trace['line'])) {
                $messageToDump .= "              " . $trace['file'] . ':' . $trace['line'] . PHP_EOL;
            }
        }

        static::dump($messageToDump, $indexOfBacktraceToRead + 1, static::METHOD_ECHO, 'ermineexceptiondump');
    }

    protected static function displayDumpStyle()
    {
        if (!static::$isStyleDisplayed) {
            echo '<style>
                pre.erminedump {
                    border: 1px solid darkgrey;
                    border-radius: 5px;
                    background-color: lightgrey;
                    padding: 5px;
                }
                pre.ermineexceptiondump {
                    background-color: lightcoral;
                }
            </style>';
            static::$isStyleDisplayed = true;
        }
    }

    /**
     * @param string $routeName
     * @param array $parameters
     * @param bool $base64Encode
     * @param bool $withDomain
     * @return string|null
     * @throws Exception
     * @todo:
     * - can be improve (what for ([^)]) regex, or (?test1|test2) regex, ...)
     * - improve if parameter value in route is 'test $1 $2'
     */
    public static function getUrl(string $routeName, array $parameters = [], bool $base64Encode = false, bool $withDomain = false)
    {
        $config = Registry::get('config');

        if (!isset($config->routes->$routeName)) {
            return null;
        }

        $route = $config->routes->$routeName;

        if (!isset($route->regex)) {
            return null;
        }

        // index parameters and set their values
        $routeParametersIndexedList = [];
        foreach ($parameters as $parameterName => $parameterValue) {
            if (
                isset($route->$parameterName) &&
                preg_match('/^\$\d+$/', $route->$parameterName)
            ) {
                $routeParametersIndexedList[(int)str_replace('$', '', $route->$parameterName) - 1] = $parameterValue;
            }
        }

        // seek in regex parameters. They should be like (\d+) or anything between parenthesis
        $result = preg_split('/(\(.*\))/U', $route->regex);
        for ($i = 0; $i < count($result) - 1; $i++) {
            if (isset($routeParametersIndexedList[$i])) {
                $result[$i] .= $routeParametersIndexedList[$i];
            }
        }
        $url = rtrim(ltrim(implode('', $result), '^'), '$');

        if ($base64Encode) {
            $url = base64_encode($url);
        }

        if ($withDomain && isset($config->url->domain)) {
            $domain = $config->url->domain;

            $url = rtrim($domain, '/') . $url;
        }
        if ($withDomain && !isset($config->url->domain)) {
            throw new Exception('Domain is not set in config file.');
        }

        return $url;
    }

    /**
     * @param int $nb
     * @param string $singular
     * @param string $plural
     * @return string
     * @todo: write tests
     */
    static function plural(int $nb, string $singular, string $plural): string
    {
        return ($nb > 1 ? $plural : $singular);
    }

    /**
     * @param $passwordLength
     * @return string
     * @todo split $alphabet in several and add options (withMajor, withMinor, withNumber, withSpecial)
     * @todo should be rewrite
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
     * @todo should be rewrite
     */
    static function dateFormatted(string $dateSystem, string $locale = 'fr'): string
    {
        return date('d/m/Y', strtotime($dateSystem));
    }

    /**
     * @param $number
     * @param string $locale
     * @param int $decimals
     * @return string
     * @todo format number in function of $locale
     * @todo should be rewrite
     */
    static function numberFormatted($number, string $locale = 'fr', int $decimals = 2)
    {
        return number_format($number, $decimals, ',', ' ');
    }

    /**
     * @param $price
     * @param $locale
     * @return string
     * @todo format number in function of $locale
     * @todo should be rewrite
     */
    static function priceFormatted($price, $locale = 'fr')
    {
        return static::numberFormatted($price, $locale) . '€';
    }

}
