<?php

namespace ermine;

use Exception;

class controller {
    
    /**
     * Vue attachée au controller
     * @var view
     */
    public $view;

    /**
     * @param $spacenameRoot
     * @throws Exception
     */
    public function __construct($spacenameRoot) {
        $this->init($spacenameRoot);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function init($spacenameRoot) {
        $this->view = new view(str_replace('controllers/', '', utils::getFileFromClass(get_class($this), $spacenameRoot)));
    }
    
    public function action() {
        $this->view->render();
    }
    
}
