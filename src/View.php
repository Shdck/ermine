<?php

namespace Ermine;

use Ermine\Exceptions\ConfigException;
use Ermine\Exceptions\Error404Exception;
use Exception;

class View
{

    /**
     * Variables usable in view
     * @var array
     */
    protected $viewVars = [];

    /**
     * Path to view
     * @var string
     */
    protected $viewPath;

    /**
     * Path to layout used as template
     * @var string
     */
    protected $layoutPath;

    /**
     * Is layout enabled
     * @var boolean
     */
    protected $layoutEnabled = true;

    /**
     * Should view be rendered
     * @var boolean
     */
    protected $viewRendered = true;

    /**
     * @param string $viewPath
     * @throws Exception
     */
    public function __construct(string $viewPath = null)
    {
        if (empty($viewPath)) {
            $this->setViewRendered(false);
            return;
        }

        $this->setViewPath($viewPath);
        $this->setLayoutPath($this->getDefaultLayoutPath());
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->viewVars[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return ($this->viewVars[$name] ?? null);
    }

    /**
     * @return string
     * @throws ConfigException
     */
    public function getDefaultLayoutPath(): string
    {
        $config = Registry::get('config');
        if (!isset($config->application->rootPath)) {
            throw new ConfigException('Root path not set');
        }
        if (!isset($config->view->path)) {
            throw new ConfigException('View path not set');
        }
        if (!isset($config->view->layoutPath)) {
            throw new ConfigException('Layout path not set');
        }

        return $config->application->rootPath . $config->view->path . $config->view->layoutPath;
    }

    /**
     * Render the page
     * @return static
     * @throws Error404Exception
     */
    public function render(): View
    {
        if ($this->isViewRendered()) {
            $viewPath = $this->getViewPath();
            if (empty($viewPath)) {
                throw new Error404Exception('View file path not set');
            }
            if (!file_exists($viewPath)) {
                throw new Error404Exception('View file not found ' . $viewPath);
            }

            if ($this->isLayoutEnabled()) {
                $layoutPath = $this->getLayoutPath();
                if (empty($layoutPath)) {
                    throw new Error404Exception('Layout file path not set');
                }
                if (!file_exists($layoutPath)) {
                    throw new Error404Exception('Layout file not found ' . $viewPath);
                }
                require($layoutPath);
            } else {
                require($viewPath);
            }
        }

        return $this;
    }

//    /**
//     * @param string $partialPath
//     * @param array $variables
//     * @throws Exception
//     */
//    public function getPartial(string $partialPath, array $variables=[]) {
//
//        $partial = new partial($partialPath);
//
//        if (!is_array($variables)) {
//            throw new Exception('Parameter $vars must be an array.');
//        }
//
//        foreach($variables as $varName => $value) {
//            $partial->assign($varName, $value);
//        }
//
//        $partial->render();
//    }

//    /**
//     * @param $filePath
//     * @param array $vars
//     * @return false|string
//     * @throws Exception
//     */
//    public function getHtml($filePath, array $vars=[]) {
//        ob_start();
//        $this->getPartial($filePath, $vars);
//        return ob_get_clean();
//    }

    /**
     * @return string
     */
    public function getViewPath(): string
    {
        return $this->viewPath;
    }

    /**
     * @param string $viewPath
     * @return View
     * @throws Error404Exception
     */
    public function setViewPath(string $viewPath): View
    {
        $this->viewPath = $viewPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutPath(): string
    {
        return $this->layoutPath;
    }

    /**
     * @param string $layoutPath
     * @return View
     * @throws Error404Exception
     */
    public function setLayoutPath(string $layoutPath): View
    {
        if (!file_exists($layoutPath)) {
            throw new Error404Exception('Layout file not found ' . $layoutPath);
        }

        $this->layoutPath = $layoutPath;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLayoutEnabled(): bool
    {
        return $this->layoutEnabled;
    }

    /**
     * @param bool $layoutEnabled
     * @return View
     */
    public function setLayoutEnabled(bool $layoutEnabled): View
    {
        $this->layoutEnabled = $layoutEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isViewRendered(): bool
    {
        return $this->viewRendered;
    }

    /**
     * @param bool $viewRendered
     * @return View
     */
    public function setViewRendered(bool $viewRendered): View
    {
        $this->viewRendered = $viewRendered;
        return $this;
    }

}
