<?php

namespace Ermine;

use ermine\exceptions\configException;
use Exception;
use stdClass;

class Application
{

    const DEFAULT_INI_FILEPATH = '../config/default.ini';
    const PROJECT_INI_FILEPATH = '../../../config/application.ini';

    /**
     * Controller de l'application
     * @var controller
     */
    private $controller;

    /**
     * @param $spaceNameRoot
     * @throws Exception
     */
    public function __construct($spaceNameRoot = null)
    {
        $this->init($spaceNameRoot);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function init($spaceNameRoot = null)
    {

        $this
            ->loadConfigFile(static::DEFAULT_INI_FILEPATH)
            ->loadConfigFile(static::PROJECT_INI_FILEPATH);

        if (!$spaceNameRoot && !isset(registry::get('config')->application->spacenameRoot)) {
            throw new configException('Space name root not defined');
        }

        utils::dump($_SERVER);

//        $this->controller = new controller($spaceNameRoot);

        registry::set('application', $this);
    }

    /**
     * @param string $filePath
     * @return application
     */
    private function loadConfigFile(string $filePath): self
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

    public function getConfig()
    {
        $config = registry::get('config');
        if (is_null($config)) {
            $config = new stdClass();
            registry::set('config', $config);
        }

        return $config;
    }

    public function mergeConfig($ini)
    {
        $config = $this->getConfig();
        foreach ($ini as $key => $value) {
            $config->$key = $value;
        }
        registry::set('config', $config);
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
     */
    public function getController(): controller
    {
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
