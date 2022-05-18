<?php

namespace Ermine;

use Exception;
use stdClass;

class Application
{

    const DEFAULT_INI_FILEPATH = __DIR__ . '/config/default.ini';

    /**
     * Controller de l'application
     * @var controller
     */
    private $controller;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function init()
    {
        $this->loadConfigFile(static::DEFAULT_INI_FILEPATH);

        Registry::set('application', $this);
    }

    /**
     * @param string $filePath
     * @return self
     */
    public function loadConfigFile(string $filePath): self
    {
        if (file_exists($filePath)) {
            $pathInfo = pathinfo($filePath);
            switch (strtolower($pathInfo['extension'])) {
                case 'ini':
                    $this->mergeConfig($this->loadIniFile($filePath));
                    break;
                case 'json':
                    $this->mergeConfig($this->loadJsonFile($filePath));
                    break;
            }
        }

        return $this;
    }

    /**
     * @return stdClass
     */
    public function getConfig(): stdClass
    {
        $config = Registry::get('config');
        if (is_null($config)) {
            $config = new stdClass();
            Registry::set('config', $config);
        }

        return $config;
    }

    /**
     * @param stdClass $ini
     * @return void
     */
    public function mergeConfig(stdClass $ini)
    {
        Registry::set(
            'config',
            $this->mergeObjects(
                $this->getConfig(),
                $ini
            )
        );
    }

    private function mergeObjects($objectToMerge, $objectToMergeWith)
    {
        if (is_null($objectToMerge)) {
            $objectToMerge = new stdClass();
        }
        foreach ($objectToMergeWith as $key => $value) {
            if (!isset($objectToMerge->$key)) {
                $objectToMerge->$key = new stdClass();
            }
            switch (gettype($value) . '-' . gettype($objectToMerge->$key)) {
                case 'object-object':
                    $objectToMerge->$key = $this->mergeObjects($objectToMerge->$key, $value);
                    break;
                default:
                    $objectToMerge->$key = $value;
            }
        }
        return $objectToMerge;
    }

    /**
     * @param string $filePath
     * @return stdClass
     */
    private function loadIniFile(string $filePath): stdClass
    {
        if (file_exists($filePath)) {
            // json_decode(json_encode([])) convert array and subarray as stdClass()
            return json_decode(
                json_encode(
                    parse_ini_file(
                        $filePath,
                        true,
                        INI_SCANNER_TYPED
                    )
                ),
                false
            );
        }

        return new stdClass();
    }

    /**
     * @param string $filePath
     * @return stdClass
     */
    private function loadJsonFile(string $filePath): stdClass
    {
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), false);
        }

        return new stdClass();
    }

    /**
     * @return controller
     * @throws Exception
     */
    public function getController(): controller
    {
        if (is_null($this->controller)) {
            $this->setController(Controller::factory());
        }

        return $this->controller;
    }

    /**
     * @param controller $controller
     * @return static
     */
    public function setController(controller $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

}
