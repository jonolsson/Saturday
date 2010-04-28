<?php

class api_frontcontroller {
    
    private $request = null;

    private $response = null;

    private $route = null;

    private $controller = null;

    private $action = null;

    function __construct() {
        try {
            $this->request = api_request::getInstance();
//            echo "<pre>";
//            print_r( $this->request);
            $this->response = api_response::getInstance();
            $this->loadRoutes();
        } catch(api_exception $e) {
            $this->catchFinalException($e);
        } catch( Exception $e ) {
            $this->catchFinalException($e);
        }
    }

    function dispatch() {
        try {
            $this->loadController();
            $this->loadView();
            $this->processController();
            $content = $this->renderView();
            $this->loadLayout();
            $this->layoutSetContent($content);
            $content = $this->renderLayout();
            echo $content;
            $this->response->send();
        } catch(Exception $e) {
            $this->catchFinalException($e);
        }
    }

    public function loadRoutes() {
        $routing = new api_routing();
        $route = $routing->getRoute($this->request);
       // print_r($route);
        if (is_null($route) || !is_array($route)) {
            throw new api_exception_NoControllerFound();
        }

        $this->route = &$route;
    }

    public function loadView() {
        $this->view = (isset($this->route['view']) ? $this->route['view'] : $this->ctrl); // Or some default
        $view = new api_view($this->route);
        $this->controller->setView($view);
    }
    
    public function renderView() {
        return $this->controller->renderView();
    }

    public function loadLayout() {
        $this->layout = $this->route['view']; // Or some default
        $layout = new api_layout($this->route);
        $this->controller->setLayout($layout);
    }
    
    public function layoutSetContent($content) {
        $this->controller->setLayoutContent($content);
    }

    public function renderLayout() {
        return $this->controller->renderLayout();
    }
    private function loadController() {
        
        $ctrl = $this->route['controller'];
        $ctrl = "controllers_".$ctrl;
        if (!class_exists($ctrl)) {
            throw new api_exception_NoControllerFound("Controller $ctrl not found");
        }

        $params = new api_params($this->route);
        $this->action = $this->route['action'];
        $this->controller = new $ctrl($params, $this->request);
    }

    private function processController() {
        $action = $this->action."Action";
        //try {
            if (!method_exists($this->controller, $action)) {
                throw new api_exception_NoActionFound("Action $action not found");
            }
            $this->controller->$action();
        //}// catch(Exception $e) {
         //   $this->catchException($e, array('command' => $this->route['command']));
        //}
    }
/**
     * Adds Exception to exceptions array. The catchException() method
     * calls this method for any non-fatal exception. The array of
     * collected exceptions is later passed to the view so it can still
     * display them.
     *
     * Exceptions are added to the array $this->exceptions.
     *
     * @param $e api_exception: Thrown exception
     * @param $prms array: Additional params passed to catchException()
     */
    private function aggregateException(api_exception $e, array $prms) {
        if (!empty($prms)) {
            foreach($prms as $n=>$v) {
                if (!empty($v)) {
                    $e->setParam($n, $v);
                }
            }
        }

        array_push($this->exceptions, $e);
    }

    /**
     * Catches any exception which has either been rethrown by the
     * catchException() method or was thrown outside of it's scope.
     *
     * Calls api_exceptionhandler::handle() with the thrown exception.
     *
     * @param   $e api_exception: Thrown exception, passed to the exceptionhandler.
     */
    private function catchFinalException(Exception $e) {
        //api_exceptionhandler::handle($e, $this);
        $params = new api_params($this->route);
        api_exceptionhandler::handle($e, new controllers_error($params, $this->request));
        if ($this->response === null) {
            die();
        }
    }

    /**
     * Catches an exception. Non-fatal and fatal exceptions are handled
     * differently:
     *    - fatal: Re-thrown so they abort the current request. Fatal
     *             exceptions are later passed on to catchFinalException().
     *    - non-fatal: Processed using aggregateException(). Additionally
     *                 they are logged by calling api_exceptionhandler::log().
     *
     * Exceptions of type api_exceptions (and subclasses) have a getSeverity()
     * method which indicates if the exception is fatal. All other exceptions
     * are assumed to always be fatal.
     *
     * @param $e api_exception: Thrown exception.
     * @param $prms array: Parameters to give more context to the exception.
     */
    private function catchException(Exception $e, $prms=array()) {
        if ($e instanceof api_exception && $e->getSeverity() === api_exception::THROW_NONE) {
            $this->aggregateException($e, $prms);
            api_exceptionhandler::log($e);
        } else {
            throw $e;
        }
    }


    /**
      Override template
      used by exception handler
      */
    public function setView($layout, $template) {
        $this->route['view']['php'] = $template;
        $this->route['layout'] = $layout;
    }

    public function setController($ctrl, $action) {
        $this->route['controller'] = $ctrl;
        $this->route['action'] = $action;
    }

    public function setControllerData($data) {
        print_r( $data);
        $this->controller->view->data = $data;
    }
}
