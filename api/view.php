<?php
class api_view {
    protected   $_viewDir = null;
    protected   $_layout = null;
    protected   $_template = null;
    protected   $_layoutDir = null;
    protected   $_viewHelperDir = null;
    protected   $_viewHelperPrefix = null;
    private     $_content = null;

    protected $outputFilters = Array('htmlspecialchars');


    function __construct($route, $file=null, $layout="main.php") {
        if ($file) {
            $this->_template = $file;
            $this->_viewDir = PROJECT_DIR."app/views/";
        } else {
            $this->_template = (isset($route['view']['php'])?$route['view']['php']:$route['action'].".php");
            $this->_viewDir = PROJECT_DIR."app/views/".$route['controller']."/";
        }

        $this->_layoutDir = PROJECT_DIR."app/layouts/";
        $this->_layout = (isset($route['layout']) ? $route['layout'] : $layout );

//        $this->userViewHelperDir = PROJECT_DIR."app/views/".
        $this->_viewHelperPrefix = "api_view_helpers_";
    }



/**
    * Returns a localized string from the stringtable
    *
    * Recurses up to the context, if the phrase is not found in the scope of this object.
    *
    * This function can be called from within a rendering template, with the shortcut function ``__``
    */
  function __($phrase) {
        $i18n = new I18n();
        $phrase = $i18n->translate($phrase);
        return $phrase;
  }

  /**
    * This function can be called from within a rendering template, with the shortcut function ``url``
    */
  function url($href = "", $args = Array()) {
    if (is_null($args)) {
      return $this->context->url($href, NULL);
    }
    return $this->context->url($href, $this->state->export($args));
  }



    function render($file=null) {
        if ($file) {
            $file = $this->_viewDir.$file;
        } else {
            $file = $this->_viewDir.$this->_template;
        }
    //if (!is_string(func_get_arg(0))) {
    //  throw new Exception("Wrong argument type. Expected string as first parameter");
    //}
    //if (func_num_args() > 1) {
    //  extract(func_get_arg(1));
    //}

        // if $file contains underscore it means its namespaced. convert to slashes
        if (strstr($file, "_")) {
            $file = str_replace("_", "/", $file);
        }
    $__template_filename__ = $file;        
    //$__template_filename__ = k_ClassLoader::SearchIncludePath(func_get_arg(0));
    if (!is_file($__template_filename__)) {
        throw new api_exception_NoViewFound("View $file Not Found");
      throw new Exception("View $file not found");
    }
    $__old_handler_e__ = $GLOBALS['_global_function_callback_e'];
    $__old_handler_____ = $GLOBALS['_global_function_callback___'];
    $__old_handler_t__ = $GLOBALS['_global_function_callback_t'];
    $__old_handler_url__ = $GLOBALS['_global_function_callback_url'];
    $GLOBALS['_global_function_callback_e'] = Array($this, 'outputString');
    $GLOBALS['_global_function_callback___'] = Array($this, '__');
    $GLOBALS['_global_function_callback_t'] = Array($this, '__');
    $GLOBALS['_global_function_callback_url'] = Array($this, 'url');
    ob_start();
    try {
      include($__template_filename__);
      $buffer = ob_get_clean();
        return $buffer;      
//      $this->content = $buffer;
//      include $this->layoutDir.$this->layout;
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
    } catch (Exception $ex) {
      ob_end_clean();
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
      throw $ex;
    }
        
        /* -- OLD --
        ob_start();
            include $this->viewDir.$this->template;
        $this->content = ob_get_contents();
        ob_end_clean();

        include $this->layoutDir.$this->layout;
         */
    }
  function outputString($str) {
    foreach ($this->outputFilters as $callback) {
      $str = call_user_func($callback, $str);
    }
    //echo $str;
    return $str;
  }

    // run view helpers
    function __call($method, $args) {
        $class = $this->_viewHelperPrefix . $method;
        $vh = new $class();
        if (method_exists($class, 'setView')) {
            $vh->setView($this);
        }
        return $vh->$method($args);
    }

    function t($str) {
        return $str;
    }

    function clearVars() {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ('_' != substr($key, 0, 1)) {
                unset($this->$key);
            }
        }
    }

    function partiali($template, $model=array()) {
        //print_r( $this->t($model['header']));
    $__old_handler_e__ = $GLOBALS['_global_function_callback_e'];
    $__old_handler_____ = $GLOBALS['_global_function_callback___'];
    $__old_handler_t__ = $GLOBALS['_global_function_callback_t'];
    $__old_handler_url__ = $GLOBALS['_global_function_callback_url'];
    $GLOBALS['_global_function_callback_e'] = Array($this, 'outputString');
    $GLOBALS['_global_function_callback___'] = Array($this, '__');
    $GLOBALS['_global_function_callback_t'] = Array($this, '__');
    $GLOBALS['_global_function_callback_url'] = Array($this, 'url');
        if (!empty($model)) {
            foreach($model as $key=>$value) {
                $this->$key = $value;
            }
        }
        ob_start();
        include $this->viewDir."_".$template;
        $c = ob_get_clean();
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
       // ob_end_clean();
        //$class = $this->viewHelperPrefix."partial";
        //$vh = new $class();
        //return $vh->partial($this->viewDir."_".$template, $model);
        return $c;
        //echo $c;
    }
}
