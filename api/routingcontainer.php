<?php

class api_routingcontainer {
    public function __construct($routing, $request) {
        // Load routing configuration
        require PROJECT_DIR . 'config/commandmap.php';
//        print_r($m);
    }
}

