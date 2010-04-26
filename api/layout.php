<?php
class api_layout {
    protected   $_layout = null;
    protected   $_layoutDir = null;
    protected   $_viewHelperDir = null;
    protected   $_viewHelperPrefix = null;
    private     $_content = null;

    protected $outputFilters = Array('htmlspecialchars');

    function __construct($route, $layout="main.php") {
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

  function setLayoutContent($content) {
    $this->_content = $content;
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
  
    function clearVars() {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ('_' != substr($key, 0, 1)) {
                unset($this->$key);
            }
        }
    }

 function render($file=null) {
        if ($file) {
            $file = $this->_layoutDir.$file;
        } else {
            $file = $this->_layoutDir.$this->_layout;
        }
            
    //if (!is_string(func_get_arg(0))) {
    //  throw new Exception("Wrong argument type. Expected string as first parameter");
    //}
    //if (func_num_args() > 1) {
    //  extract(func_get_arg(1));
    //}

    $__template_filename__ = $file;        
    //$__template_filename__ = k_ClassLoader::SearchIncludePath(func_get_arg(0));
    if (!is_file($__template_filename__)) {
      throw new Exception("Failed opening '".func_get_arg(0)."' for inclusion. (include_path=".ini_get('include_path').")");
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

      //$this->_content = $this->_content;
        return $buffer;      
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
function outputString($str) {
    foreach ($this->outputFilters as $callback) {
      $str = call_user_func($callback, $str);
    }
    echo $str;
  }

 }
}
