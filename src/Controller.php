<?php

namespace Ermine;

use Ermine\Exceptions\ConfigException;
use Ermine\Exceptions\Error404Exception;
use Exception;
use ReflectionClass;

abstract class Controller
{

    /** @var View $view */
    public $view;

    /** @var array $parameters */
    protected $parameters;

    /**
     * @throws Exception
     */
    public function __construct($parameters = [])
    {
        $this->parameters = $parameters;
        $this->init();
    }

    /**
     * @return Controller
     * @throws ConfigException
     * @throws Error404Exception
     */
    public static function factory(): Controller
    {
        $redirectUrl = $_SERVER['REDIRECT_URL'] ?? null;
        $config = Registry::get('config');
        $controller = null;
        $parameters = [];
        foreach ($config->routes as $route) {
            if (
                isset($route->regex) &&
                preg_match('#' . $route->regex . '#', $redirectUrl)
            ) {
                foreach ($route as $key => $value) {
                    switch ($key) {
                        case 'regex':
                            // Nothing to do but catch it
                            break;
                        case 'controller':
                            $controller = preg_replace('#' . $route->regex . '#', $route->controller, $redirectUrl);
                            break;
                        default:
                            $parameters[$key] = preg_replace('#' . $route->regex . '#', $route->$key, $redirectUrl);
                    }
                }
                break;
            }
        }

        if (is_null($controller)) {
            throw new ConfigException("No route found for $redirectUrl");
        }

        $controller = preg_replace_callback(
            '#\b\w+\b#',
            function ($matches) {
                return ucfirst($matches[0]);
            },
            $controller
        );

        if (!class_exists($controller)) {
            throw new Error404Exception('Controller not found: ' . $controller);
        }

        return new $controller($parameters);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function init()
    {
        $this->setView(new View($this->getViewPath()));
    }

    /**
     * @return void
     * @throws Error404Exception
     * @noinspection PhpUnused
     */
    public function action()
    {
        $this->view->render();
    }

    /**
     * @return string
     * @throws ConfigException
     */
    private function getViewPath(): string
    {
        $config = Registry::get('config');

        if (!isset($config->application->rootPath)) {
            throw new ConfigException('Root path not set in config files');
        }
        if (!isset($config->view->path)) {
            throw new ConfigException('View path not set in config files');
        }
        if (!isset($config->view->extension)) {
            throw new ConfigException('View extension not set in config files');
        }

        $viewDirectoryPath = $config->application->rootPath . $config->view->path;

        $reflectionClass = new ReflectionClass($this);
        $className = $reflectionClass->getName();
        $classNameSpace = $reflectionClass->getNamespaceName();
        return strtolower(
            $viewDirectoryPath .
            '/' .
            str_replace($classNameSpace . '\\', '', $className) .
            $config->view->extension
        );
    }

    /**
     * @return View
     * @noinspection PhpUnused
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * @param View $view
     * @return Controller
     */
    public function setView(View $view): Controller
    {
        $this->view = $view;
        return $this;
    }

    public function __get($name)
    {
        return $this->parameters[$name] ?? null;
    }

}
