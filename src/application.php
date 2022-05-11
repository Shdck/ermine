<?php

namespace ermine;

use ermine\exceptions\configException;
use Exception;
use stdClass;

class application {

    const DEFAULT_INI_FILEPATH = '../config/default.ini';
    const PROJECT_INI_FILEPATH = '../../../config/application.ini';

    /**
     * Controller de l'application
     * @var view
     */
    public $controller;

    /**
     * @param $spaceNameRoot
     * @throws Exception
     */
    public function __construct($spaceNameRoot=null) {
        $this->init($spaceNameRoot);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function init($spaceNameRoot=null) {

        $this
            ->loadConfigFile(static::DEFAULT_INI_FILEPATH)
            ->loadConfigFile(static::PROJECT_INI_FILEPATH);

        if(!$spaceNameRoot && !isset(registry::get('config')->application->spacenameRoot)) {
            throw new configException('Space name root not defined');
        }

        registry::set('controller', new controller($spaceNameRoot));
    }

    /**
     * @param string $filePath
     * @return $this
     */
    private function loadConfigFile($filePath) {
        if (file_exists($filePath)) {
            $pathInfo =pathinfo($filePath);
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

    public function getConfig() {
        $config = registry::get('config');
        if (is_null($config)) {
            $config = new stdClass();
            registry::set('config', $config);
        }

        return $config;
    }

    public function mergeConfig($ini) {
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
    private function loadIniFile($filePath) {
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
    private function loadJsonFile($filePath) {
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), false);
        }

        return new stdClass();
    }

}
