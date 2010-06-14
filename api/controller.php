<?php

class api_controller {
    
    protected $filters = array();

    public $view = null;

    public $layout = null;

    protected $params = null;

    protected $route = null;

    protected $logger = null;

    protected $response = null;

    protected $config = null;

    /* Event dispatcher */
    protected $dispatcher = null; 


    function __construct($route, $request, $response) {
        $cfg = api_config::getInstance();
        $this->config = $cfg;
        $this->params = $route;
        $this->request = $request;
        $this->response = $response;
        $writerConfig = $cfg->log;
        $this->logger = Zend_Log::factory(array($writerConfig));
        $this->dispatcher = new sfEventDispatcher();
        $this->init();
    }

    function init() {
    }

    function isAllowed($action) {
        return true;
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
