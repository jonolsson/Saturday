<?php

class database_Base {
    protected $nameClosing;
    protected $nameOpening;

    public function __construct() {
        echo "database Base constructor";
    }

    public function quoteName( $name ) {
        return $this->nameOpening
            .str_replace( $this->nameClosing, $this->nameClosing.$this->nnameClosing, $name )
            .$this->nameClosing;
    }
    
   
}
