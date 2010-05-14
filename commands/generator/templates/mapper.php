<?php
class mappers_nameplural extends api_mapper {
    protected $table = "nameplural";
    protected $class = "name";

    function __construct() {
        parent::__construct($this->table);
    }
}
