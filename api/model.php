<?php
class api_model {
    public $created_at = null; 
    public $updated_at = null; 
    public $errors = null;

    protected $values = array();

    function __construct($params=null) {
        $this->created_at = date("Y-m-d H:m:i"); 
        $this->updated_at = date("Y-m-d H:m:i");

        $this->errors = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        if ($params) {
            foreach($params as $key=>$value) {
                $this->$key = $value;
            }
        }
    }

    function __set($name, $value) {
        $this->values[$name] = $value;
    }

    function __get($name) {
        return (isset($this->values[$name]) ? $this->values[$name] : null);
    }

    /**
     * Validation hook
     */
    function validate() {}

    /**
     * Insert validation hook
     */
    function insertValidate() {}

    /**
     * Update validation hook
     */
    function updateValidate() {}

    function isValid() {
        $this->validate();
        if (count($this->errors) > 0) {
            return false;
        }
        return true;
    }

    /* fill model with request params */
    function fill($request) {
        foreach($request->getParams() as $field=>$value) {
            $this->$field = $value;
        }
    }
}
