<?php
class api_exception_NoActionFound extends api_exception {
    public function __construct($msg="Action not found") {
        parent::__construct();
        $this->setMessage($msg);
        $this->setSeverity(api_exception::THROW_FATAL);
    }
}
