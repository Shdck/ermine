<?php

namespace Ermine;

abstract class Model
{

    public function __construct($data = [])
    {
        $this->initData($data);
    }

    abstract public function save();

    abstract public function delete();

    /**
     * @param array $data
     * @return Model
     */
    abstract protected function initData(array $data): Model;

}