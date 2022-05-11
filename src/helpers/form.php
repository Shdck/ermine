<?php

namespace ermine\helpers;

use ermine\helpers;
use Exception;

class form extends helpers {
    
    /**
     * @var array inputs du form
     */
    protected $_inputList = [];
    
    /**
     * @var array type d'inputs autorisés
     */
    protected $_allowedInputType = ['text', 'email', 'hidden', 'password', 'submit', 'select', 'textarea', 'checkbox', 'radio', 'tagsSelect', 'button', 'reset', 'a'];
    
    /**
     * @var array type d'inputs autorisés
     */
    protected $_formConrolInputType = ['text', 'email', 'password', 'select', 'textarea', 'tagsSelect'];
    
    /**
     * @var array type d'inputs button
     */
    protected $_buttonInputType = ['submit', 'button', 'reset'];
    
    /**
     * @var array liste des erreurs du formulaire 
     */
    protected $_tabErrors = [];

    /**
     * Constructeur de la class
     * @throws Exception
     */
    public function __construct(array $params=[]) {
       parent::__construct(dirname(__FILE__).'/../../views/_helpers/form.phtml');

        $defaultParams = [
            'id' => null,
            'action' => null,
            'method' => 'POST',
        ];
        
        $params = $params + $defaultParams;


        if (isset($params['id'])) {
            $this->setId($params['id']);
        }

        if (isset($params['action'])) {
            $this->setAction($params['action']);
        }

        if (isset($params['method'])) {
            $this->setMethod($params['method']);
        }
    }
    
    /**
     * @return string
     */
    public function getId() {
        return $this->v('id');
    }
    
    /**
     * @return string
     */
    public function getAction() {
        return $this->v('action');
    }
    
    /**
     * @return string
     */
    public function getMethod() {
        return $this->v('method');
    }

    /**
     * @param string
     * @return static
     */
    public function setId($value) {
        $this->assign('id', $value);
        return $this;
    }

    /**
     * @param string
     * @return static
     */
    public function setAction($value) {
        $this->assign('action', $value);
        return $this;
    }

    /**
     * @param string
     * @return static
     */
    public function setMethod($value) {
        $this->assign('method', $value);
        return $this;
    }
    
    protected function getDefaultClass($tabInput) {
        $retour = '';
        if (!isset($tabInput['type']) || in_array($tabInput['type'], $this->_formConrolInputType)) {
            $retour = 'form-control';
        } elseif (in_array($tabInput['type'], $this->_buttonInputType)) {
            $retour = 'btn btn-xee';
        }
        
        return $retour;
    }

    /**
     * @param array
     * @return static
     * @throws Exception
     */
    public function addInput(Array $tabInput=[]) {
        $defaultInput = [
            'type' => 'text',
            'label' => null,
            'name' => null,
            'id' => null,
            'class' => $this->getDefaultClass($tabInput),
            'placeholder' => null,
            'value' => null,
            'checked' => false,
            'isSubmit' => false,
            'validator' => null,
            'errorMessage' => null,
            'error' => null,
            'href' => null,
            'inputAddBefore' => [],
            'inputAddAfter' => [],
        ];
        
        $tabInput = $tabInput + $defaultInput;
        
        if (!in_array($tabInput['type'], $this->_allowedInputType)) {
            throw new Exception('Le type d\'input '.$tabInput['type'].' n\'est pas autorisé.');
        }
        
        if (!is_array($tabInput['inputAddBefore'])) {
            $tabInput['inputAddBefore'] = [$tabInput['inputAddBefore']];
        }
        
        if (!is_array($tabInput['inputAddAfter'])) {
            $tabInput['inputAddAfter'] = [$tabInput['inputAddAfter']];
        }
        
        $this->_inputList[] = $tabInput;
        
        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setInputValue(string $name, string $value): self {
        $this->setInputAttribute($name, 'value', $value);

        return $this;
    }

    /**
     * @param string $name
     * @param string $attributeName
     * @param string $value
     * @return $this
     */
    public function setInputAttribute(string $name, string $attributeName, string $value): self {
        foreach ($this->_inputList as &$input) {
            if ($input['name'] == $name) {
                $input[$attributeName] = $value;
                break;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getInputs(): array {
        return $this->_inputList;
    }

    /**
     * @param string $name
     * @param string|null $type
     * @return array
     */
    public function getInputsByName(string $name, string $type=null): array {
        $tabInputs = [];
        
        foreach($this->_inputList as $input) {
            if ($input['name'] == $name) {
                $tabInputs[] = $this->formatInput($input, $type);
            }
        }
        
        return $tabInputs;
    }

    /**
     * @param string $name
     * @param string|null $type
     * @return mixed|null
     */
    public function getInputValue(string $name, string $type=null) {
        foreach($this->_inputList as $input) {
            if ($input['name'] == $name) {
               return $this->formatInput($input['value'], $type);
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isFormValide(): bool {
        $tabInputs = $this->getInputs();
        $isValid = true;
        
        foreach ($tabInputs as $key => $input) {
            $isValid = $this->isInputValide($input, $key) && $isValid;
        }
        
        return $isValid;
    }

    /**
     * @param array $input
     * @param string $key
     * @return bool
     */
    protected function isInputValide(array $input, string $key): bool {
        $isValid = true;
        if ($input['validator']) {
            $validatorClass = $input['validator'];
            $validator = new $validatorClass($input);
            if (!$validator->isValid()) {
                $this->_inputList[$key]['error'] = $validator->getError();
                $this->addError($validator->getError());
                $isValid = false;
            }
        }
        
        return $isValid;
    }
    
    public function addError($error) {
        $this->_tabErrors[] = $error;
        
        return $this;
    }
    
    public function getErrors() {
        return $this->_tabErrors;
    }

    /**
     * @param $input
     * @throws Exception
     */
    protected function _displayInput($input) {
        $this->getPartial(
                dirname(__FILE__).'/../../views/_partials/formInput.phtml',
                [
                    'input' => $input,
                ]
        );
    }

    protected function formatInput($value, $type) {
        switch($type) {
            case 'float':
                $value = (float)str_replace(',', '.', $value);
                break;
            case 'boolean':
                $value = (boolean)$value;
                break;
            default:
        }

        return $value;
    }
}
