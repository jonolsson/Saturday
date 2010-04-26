<?php
class api_view_helpers_partial {
    private $view = null;
    protected $_extension = ".php";

    function partial() {
        if (0==func_num_args()) {
            return;
        }
        $view = $this->cloneView();
        $args = func_get_arg(0);
        $template = $args[0];
        if (count($args)>1) {
            $model = $args[1];
            //print_r($model);
            $view->model = $model;
            foreach($model as $key=>$value) {
                $view->$key = $value;
            }

        }

        //echo func_get_arg(0);
        //print_r($template);
        //include $template;
        return $view->render("_".$template.$this->_extension);
    }

    public function cloneView()
    {
        $view = clone $this->view;
        $view->clearVars();
        return $view;
    }

    function setView($view) {
        $this->view = $view;    
    }
}

