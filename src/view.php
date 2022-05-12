<?php

namespace Ermine;

use Exception;

class view {
    
    /**
     * @var array variables utilisables dans le template 
     */
    protected $_viewVars = [];
    
    /**
     * @var string fichier de la vue
     */
    protected $_filePath;
    
    /**
     * @var string fichier du template
     */
    protected $_layoutPath;
    
    /**
     * @var boolean doit on afficher le template
     */
    protected $_enableLayout = true;
    
    /**
     * @var boolean doit on afficher la vue
     */
    protected $_enableView = true;

    /**
     * view constructor.
     * @param string $controllerName
     * @throws Exception
     */
    public function __construct(string $controllerName=null) {
        if (empty($controllerName)) {
            $this->disableView();
            return;
        }

        if (SH_DEFAULT_VIEW_FOLDER && SH_DEFAULT_VIEW_EXTENSION) {
            $filename = $controllerName;
            $this->setViewPath(SH_DEFAULT_VIEW_FOLDER.$filename.SH_DEFAULT_VIEW_EXTENSION);
        }
        
        if (SH_DEFAULT_LAYOUT) {
            $this->setLayoutPath(SH_DEFAULT_LAYOUT);
        }
    }

    /**
     * @param string $viewPath
     * @return view
     * @throws Exception
     */
    public function setViewPath(string $viewPath) {
        if (!is_string($viewPath)) {
            throw new Exception('wrong parameter $viewPath. String expected.');
        }
        
        if (!file_exists($viewPath)) {
            throw new Exception('File not found ' . $viewPath);
        }

        $this->_filePath = $viewPath;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getViewPath() {
        return $this->_filePath;
    }

    /**
     * @param string $layoutPath
     * @return view
     * @throws Exception
     */
    public function setLayoutPath(string $layoutPath) {
        if (!is_string($layoutPath)) {
            throw new Exception('wrong parameter $layoutPath. String expected.');
        }
        
        if (!file_exists($layoutPath)) {
            throw new Exception('File not found ' . $layoutPath);
        }
        
        $this->_layoutPath = $layoutPath;
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function getLayoutPath() {
        return $this->_layoutPath;
    }

    /**
     * @param string $varName
     * @param mixed $value
     * @return $this
     */
    public function assign(string $varName, $value): self {
        $this->_viewVars[$varName] = $value;
        
        return $this;
    }
    
    /**
     * Obtenir la valeur d'une variable
     * @param string
     * @return mixed
     */
    public function v($varName) {
        return ($this->_viewVars[$varName] ?? null);
    }
    
    /**
     * Activer le template
     * @return view
     */
    public function enableLayout() {
        $this->_enableLayout = true;
        return $this;
    }
    
    /**
     * Désactiver le template
     * @return view
     */
    public function disableLayout() {
        $this->_enableLayout = false;
        return $this;
    }
    
    /**
     * Le template est il activé
     * return boolean
     */
    public function isLayoutEnabled() {
        return $this->_enableLayout;
    }
    
    /**
     * Activer la vue
     * @return view
     */
    public function enableView() {
        $this->_enableView = true;
        return $this;
    }
    
    /**
     * Désactiver la vue
     * @return view
     */
    public function disableView() {
        $this->_enableView = false;
        return $this;
    }
    
    /**
     * La vue est elle activée
     * return boolean
     */
    public function isViewEnabled() {
        return $this->_enableView;
    }
    
    /**
     * Fait le rendu html de la page
     * @return view
     */
    public function render() {
        $viewFile = $this->getViewPath();
        
        if ($this->isViewEnabled() && $viewFile) {
            $layoutFile = $this->getLayoutPath();
            if ($this->isLayoutEnabled() && $layoutFile) {
                require($layoutFile);
            } else {
                require($viewFile);
            }
        }
        
        return $this;
    }

    /**
     * @param $filePath
     * @param array $vars
     * @throws Exception
     */
    public function getPartial($filePath, array $vars=[]) {
        
        $partial = new partial($filePath);
        
        if (!is_array($vars)) {
            throw new Exception('Parameter $vars must be an array.');
        }
        
        foreach($vars as $varName => $value) {
            $partial->assign($varName, $value);
        }

        $partial->render();
    }

    /**
     * @param $filePath
     * @param array $vars
     * @return false|string
     * @throws Exception
     */
    public function getHtml($filePath, array $vars=[]) {
        ob_start();
        $this->getPartial($filePath, $vars);
        return ob_get_clean();
    }
    
}
