<?php
class Articles extends Mapper {
  
  function __construct( $table ) {
      $class = get_class($this);
      print_r( strToLower($class));
      echo "\n";
    parent::__construct( $table );
  }
}

