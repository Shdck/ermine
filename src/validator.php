<?php

namespace ermine;

class validator {
    
    /**
     * @var array
     */
    protected $_input = null;
    
    /**
     * @var string
     */
    protected $_error = null;

    /**
     * @param []
     */
    public function __construct($input) {
        $this->_input = $input;
    }

    public function isValid() {
        $retour = true;
        
        if (is_null($this->_input['value'])) {
            $this->setError('Cette valeur est null');
            $retour = false;
        }
        
        return $retour;
    }
    
    public function getError() {
        return $this->_error;
    }
    
    public function setError($errorMessage) {
        if ($this->_input['errorMessage']) {
            $this->_error = $this->_input['errorMessage'];
        } else {
            $this->_error = $errorMessage;
        }
    }
    
    public static function check($value) {
        $validator = new static(['value' => $value, 'errorMessage' => null]);
        return $validator->isValid();
    }

}
