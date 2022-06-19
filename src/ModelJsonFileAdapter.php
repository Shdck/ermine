<?php

namespace Ermine;

use Exception;

/**
 * @property array $columns
 * @property string $filePath
 * @todo:
 * - Generate information_schema traits
 */
abstract class ModelJsonFileAdapter extends Model
{
    use ModelGetterSetterCallerTrait;

    protected static $jsonValues;
    protected static $isLoaded = false;

    /** @var string $key */
    protected $key;
    protected $columnValues = [];

    /**
     * @param array $data
     * @throws Exception
     */
    public function __construct($data = [], $key = null)
    {
        // this class must use a mapper
        if (count(class_uses($this)) == 0) {
            throw new Exception('You must use a mapper trait in your model.');
        }

        $this->key = (is_null($key) ? $this->newKey() : $key);
        parent::__construct($data);
    }

    private function newKey(): int
    {
        return (((int)max(array_keys(static::$jsonValues))) + 1);
    }

    /**
     * @return static|null
     * @throws Exception
     */
    public static function instantiate(string $key)
    {
        if (!static::$isLoaded) {
            static::$jsonValues = [];
            if (file_exists(static::fileFullPath())) {
                $fileContent = file_get_contents(static::fileFullPath());
                static::$jsonValues = json_decode($fileContent, true);
            }
        }

        if (isset(static::$jsonValues[$key])) {
            $class = get_called_class();
            return new $class(static::$jsonValues[$key], $key);
        }
        return (static::$jsonValues[$key] ?? null);
    }

    private static function fileFullPath(): string
    {
        $config = Registry::get('config');
        return $config->application->root . $config->json->dirPath . static::$filePath;
    }

    /**
     * @throws Exception
     */
    public function save()
    {
        $key = $this->getKey();
        if (empty($key)) {
            $key = $this->newKey();
            $this->setKey($key);
        }

        static::$jsonValues[$key] = $this->columnValues;
        file_put_contents(static::fileFullPath(), json_encode(static::$jsonValues));
    }

    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey(string $key): ModelJsonFileAdapter
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Suppression de l'objet
     * @throws Exception
     */
    public function delete()
    {
        $key = $this->getKey();
        if (empty($key)) {
            return;
        }

        unset(static::$jsonValues[$key]);
        file_put_contents(static::fileFullPath(), json_encode(static::$jsonValues));
    }

    protected function initData(array $data): Model
    {
        foreach (static::$columns as $columnName => $columnStructure) {
            $this->$columnName = $columnStructure['default'];
        }
        foreach ($data as $columnName => $value) {
            $this->$columnName = $value;
        }
        return $this;
    }

}