<?php

class api_controller {
    
    public $view = null;
    public $layout = null;

    protected $params = null;

    protected $route = null;

    function __construct($route, $request) {
        $this->params = $route;
        $this->request = $request;
    }

    function setView($view) {
        $this->view = $view;
    }

    function renderView() {
        return $this->view->render();
    }

    function setLayout($layout) {
        $this->layout = $layout;
    }

    function setLayoutContent($layout) {
        $this->layout->setLayoutContent($layout);
    }

    function renderLayout() {
        return $this->layout->render();
    }

    protected function redirectTo($path) {
        header("Location: $path");
    }
}
