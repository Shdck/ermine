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
        static::loadJson();
        if (count(static::$jsonValues) == 0) {
            return 1;
        }
        return (((int)max(array_keys(static::$jsonValues))) + 1);
    }

    /**
     * @return static|null
     * @throws Exception
     */
    public static function instantiate(string $key)
    {
        static::loadJson();
        if (isset(static::$jsonValues[$key])) {
            $class = get_called_class();
            return new $class(static::$jsonValues[$key], $key);
        }
        return (static::$jsonValues[$key] ?? null);
    }

    /**
     * @param array $filters - filter to apply to the request (key => value)
     * @param array $order - order by to apply to the request
     * @param integer|null $offset - offset to apply to the limit
     * @param integer|null $limit - limit to apply to the request
     * @return static[] Result of the request
     * @throws Exception
     */
    public static function list(array $filters = [], array $order = [], int $offset = null, int $limit = null): array
    {
        static::loadJson();
        $list = [];
        // query the json file
        foreach (static::$jsonValues as $key => $row) {
            $rowObject = static::instantiate($key);
            foreach ($filters as $filterKey => $filterValue) {
                switch (true) {
                    case (is_array($filterValue) && in_array($rowObject->$filterKey, $filterValue)):
                    case (is_null($filterValue) && is_null($rowObject->$filterKey)):
                    case ($rowObject->$filterKey == $filterValue):
                        // nothing to do, just check next filter
                        break;
                    default:
                        // filter does not match, skip this row
                        continue 2;
                }
            }
            $list[] = $rowObject;
        }

        // order the list
        for ($i = count($order) - 1; $i >= 0; $i--) {
            $explodedOrder = explode(' ', $order[$i]);
            $attribute = $explodedOrder[0];
            for ($j = 0; $j < count($list); $j++) {
                $row = $list[$j];
                for ($k = $j + 1; $k < count($list); $k++) {
                    $rowToCompare = $list[$k];
                    $compareResult = $row->$attribute > $rowToCompare->$attribute;
                    if (isset($explodedOrder[1]) && strtolower($explodedOrder[1]) == 'desc') {
                        $compareResult = $row->$attribute < $rowToCompare->$attribute;
                    }
                    if ($compareResult) {
                        $list[$j] = $rowToCompare;
                        $list[$k] = $row;
                        $row = $list[$j];
                    }
                }
            }
        }

        // limit the list
        if (is_null($offset) && !is_null($limit)) {
            $offset = 0;
        }
        if (!is_null($offset) && !is_null($limit)) {
            $list = array_slice($list, $offset, $limit);
        }

        return $list;
    }

    /**
     * @param array $filters - filter to apply to the request (key => value or expression)
     * @param array $order - order by to apply to the request
     * @return static|null
     * @throws Exception
     */
    public static function first(array $filters = [], array $order = [])
    {
        $list = self::list($filters, $order, 0, 1);

        return (count($list) ? $list[0] : null);
    }

    private static function loadJson()
    {
        if (!static::$isLoaded) {
            static::$jsonValues = [];
            if (file_exists(static::fileFullPath())) {
                $fileContent = file_get_contents(static::fileFullPath());
                static::$jsonValues = json_decode($fileContent, true);
            }
        }
    }

    private static function fileFullPath(): string
    {
        $config = Registry::get('config');
        return $config->application->rootPath . $config->json->directoryPath . DIRECTORY_SEPARATOR . static::$filePath;
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