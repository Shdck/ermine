<?php

namespace Ermine;

use Exception;

class partial extends view {

    /**
     * @param $filePath
     * @throws Exception
     */
    public function __construct($filePath) {
        
        $this->setViewPath($filePath);

    }
    
}
