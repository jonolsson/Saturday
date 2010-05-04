<?php

class api_controller {
    
    public $view = null;
    public $layout = null;

    protected $params = null;

    protected $route = null;

    protected $response = null;

    function __construct($route, $request, $response) {
        $this->params = $route;
        $this->request = $request;
        $this->response = $response;
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
