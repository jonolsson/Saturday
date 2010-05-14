<?php
//include 'migration_helper.php';
//require '../database.php';
//require 'migrations.php';

class commands_migration {

    private $path = "db/migrations/";

    function __construct( $settings=array() ) {
        
    }

    function migrate( $direction='up', $version=null ) {
        $migrations = glob( $this->path."*.php");
        print_r( $migrations );
        foreach( $migrations as $migration ) {
            $name = basename( $migration );
            echo "Name: $name\n";
            if ( preg_match('/^\d{14}_(.+)$/', $name, $match) ) {
                echo "Name: $name\n";
                print_r( $match );
                $class = basename( $match[1], ".php");
                echo "\ninclude: ".$this->path.$name."\n";
                include $this->path.$name;
                echo "\nclass: $class\n"; 
                // call_user_func(array($class, "up"));
        		//		$match[1] = strtolower($match[1]);
                $mig = new $class();
                $mig->$direction();
            }
        }
    }

    function blah() {
$migrations_path = "migrations/";

$migrations = glob( $migrations_path."*.php");

print_r( $migrations );
$direction = "up";
foreach( $migrations as $migration ) {
    $name = basename( $migration );
    echo $name;
    if ( preg_match('/^\d{3}_(.+)$/', $name, $match) ) {
        echo $name;
        print_r( $match );
        $class = basename( $match[1], ".php");
        echo "\ninclude: ".$migrations_path.$name."\n";
        include $migrations_path.$name;
        echo "\nclass: $class\n"; 
        // call_user_func(array($class, "up"));
		//		$match[1] = strtolower($match[1]);
        $mig = new $class();
        $mig->$direction();
    }
}
}
}

