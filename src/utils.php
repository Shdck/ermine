<?php

namespace Ermine;

class utils
{

    /**
     * Dump d'une variable
     * @param $var
     */
    static function dump($var)
    {
        if (SANDBOX) {
            echo PHP_EOL;
            echo(php_sapi_name() != 'cli' ? "<pre>" : '');
            var_dump($var);
            echo(php_sapi_name() != 'cli' ? "</pre>" : '');
            echo PHP_EOL;
        }
    }

    /**
     * Dump d'une exception
     * @param Exception $e
     */
    static function dumpException($e)
    {
        if (SANDBOX) {
            echo "<pre>";
            echo "<b>" . get_class($e) . ' : ' . $e->getMessage() . "</b>\n";
            echo "Error in file " . $e->getFile() . ":" . $e->getLine() . "\n";
            foreach ($e->getTrace() as $trace) {
                if (isset($trace['file']) && isset($trace['line'])) {
                    echo "              " . $trace['file'] . ':' . $trace['line'] . "\n";
                }
            }
            echo "</pre>";
        }
    }

    /**
     * Récupere la valeur d'une variable d'un tableau d'input ($_GET, $_POST...)
     * @param string $varname nom de la vbariable à récupérer
     * @param string $default valeur par défaut si la variable est vide
     * @param int $table table d'input à utiliser ($_GET, $_POST...)
     * @param string $callback validation des données
     * @return mixed valeur de la variable
     */
    static function getParameters(
        string $varname,
        string $default = null,
        int    $table = INPUT_GET,
        string $callback = filterCallback::BASIC_STRING
    )
    {
        $input = filter_input(
            $table,
            $varname,
            FILTER_CALLBACK,
            [
                'options' => '\ermine\filterCallback::' . $callback
            ]
        );

        if (is_null($input) || $input === false) {
            return $default;
        } else {
            return $input;
        }
    }

    /**
     * Vérifie si la variable est soumise dans un tableau d'input ($_GET, $_POST...)
     * @param string $varname nom de la vbariable à récupérer
     * @param int $table table d'input à utiliser ($_GET, $_POST...)
     * @return boolean
     */
    static function isParametersSubmit(string $varname, int $table=INPUT_GET): bool {
        $input = filter_input($table, $varname);

        return !is_null($input);
    }

    /**
     *
     * @param string $url
     * @return array
     */
    static function getClassFromUrl(string $url): array {
        global $arrRoutes, $app;

        $className = null;
        $params = [];
        $routeKey = '';

        if (isset($arrRoutes)) {
            foreach ($arrRoutes as $key => $route) {
                if (!isset($route['regex'][LANGUAGE]) && !isset($route['regex']['all'])) {
                    continue;
                }
                $regex = ($route['regex'][LANGUAGE] ?? $route['regex']['all']);

                if (preg_match('#^' . $regex . '$#', $url, $matches)) {

                    $className = $route['controller'];
                    $routeKey = $key;

                    if (isset($route['params'])) {
                        foreach ($route['params'] as $paramKey => $param) {
                            if (substr($param, 0, 1) == '$') {
                                $param = $matches[substr($param, 1)];
                            }

                            $params[$paramKey] = $param;
                        }
                    }

                    break;
                }
            }
        }

        if (!$className) {
            $tabClassName = explode('-', $url);
            for ($i = 1; $i < count($tabClassName); $i++) {
                $tabClassName[$i] = ucfirst($tabClassName[$i]);
            }
            $className = implode('', $tabClassName);
            $className = str_replace('/', '_', $className);
        }

        return ['className' => $className, 'params' => $params, 'routeKey' => (string)$routeKey];
    }

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
                utils::getParameters('controller', 'home', INPUT_GET, filterCallback::SYSTEM_STRING),
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

    public static function getUrlTitle($routeKey, $language=LANGUAGE) {
        global $arrRoutes;

        if (!isset($arrRoutes[$routeKey])) {
            return '';
        }

        $route = $arrRoutes[$routeKey];

        if (
            !isset($route['title'][$language]) &&
            !isset($route['title']['all'])
        ) {
            return '';
        }

        if (!isset($route['title'])) {
            return '';
        }

        return ($route['title'][$language] ?? $route['title']['all']);
    }

    public static function getUrlPageview($routeKey, $language=LANGUAGE) {
        global $arrRoutes;

        if (!isset($arrRoutes[$routeKey])) {
            return '';
        }

        $route = $arrRoutes[$routeKey];

        if (!isset($route['pageview'])) {
            return '';
        }

        return ($route['pageview'][$language] ?? $route['pageview']['all']);
    }

    /**
     * 
     * @param string $className
     * @param string $spacenameRoot
     * @return string
     */
    static function getFileFromClass(string $className, string $spacenameRoot): string {
        return str_replace(
            [
                '\\',
                $spacenameRoot . '/',
            ],
            [
                '/',
                '',
            ],
            $className
        );
    }

    static function plural($nb, $singular, $plural) {
        return ($nb > 1 ? $plural : $singular);
    }
    
    static function randomPassword($passwordLength=8) {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
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
    static function numberFormated($number, string $locale='fr', int $decimals=2) {
        return number_format($number, $decimals, ',', ' ');
    }

    static function priceFormated($price, $locale='fr') {
        return static::numberFormated($price, $locale) . '€';
    }

}
