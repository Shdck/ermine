<?php

namespace ermine;

class partial extends view {
    
    public function __construct($filePath) {
        
        $this->setViewPath($filePath);
        
    }
    
}
