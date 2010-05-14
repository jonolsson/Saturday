<?php
class api_model {
    public $created_at = "1977-09-19 12:12:12";
    public $updated_at = "1977-09-19 12:12:12";
    public $errors;

    protected $values = array();

    function __construct($params=null) {
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

}
