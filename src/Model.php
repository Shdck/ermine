<?php

namespace Ermine;

abstract class Model
{

    public function __construct($data = [])
    {
        $this->setData($data);
    }

    abstract public function save();

    abstract public function delete();

    /**
     * @param array $data
     * @return Model
     */
    abstract public function setData(array $data): Model;

}