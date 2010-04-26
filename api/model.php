<?php
class api_model {
    public $created_at = "1977-09-19 12:12:12";
    public $updated_at = "1977-09-19 12:12:12";

    protected $values = array();

    function __set($name, $value) {
        $this->values[$name] = $value;
    }

    function __get($name) {
        return (isset($this->values[$name]) ? $this->values[$name] : null);
    }

    function __construct() {
    }

    /*function __call($method, $args) {
        echo "Called";

    }*/
}
