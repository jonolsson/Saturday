<?php
//include 'migration_helper.php';
//require '../database.php';
//require 'migrations.php';

class commands_migration {

    private $path = "db/migrations/";

    function __construct( $settings=array() ) {
        
    }

    function migrate( $direction='up', $version=null ) {
        // Get current version
        $version = $this->getVersion();

        // Get migrations
        $migrations = glob( $this->path."*.php");
                print_r( $migrations );

        try {
        foreach( $migrations as $migration ) {
            $name = basename( $migration );
//            echo "Name: $name\n";
            if ( preg_match('/^\d{14}_(.+)$/', $name, $match) ) {
//                echo "Name: $name\n";
                $stamp = explode('_', $name);
                $stamp = $stamp[0];
                echo "stamp: ".$stamp."\n";
                echo "version: ".$version."\n";
                if ($stamp > $version) {
                    
//                print_r( $match );
                    $class = basename( $match[1], ".php");
//                echo "\ninclude: ".$this->path.$name."\n";
                    include $this->path.$name;
//                echo "\nclass: $class\n"; 
                // call_user_func(array($class, "up"));
        		//		$match[1] = strtolower($match[1]);
                    try {
                        $mig = new $class();
                        $mig->$direction();
                    }
                    catch (Exception $e) {
                        throw new Exception("Migration failed");    
                    }
                    $this->increaseVersion($stamp);
                }
            }
        }
        }
        catch(Exception $e) {
            echo "Migration failed\n";
        }
    }
    
    private function increaseVersion($version) {
        $db = api_database::factory();
        $stmt = $db->query("INSERT INTO schema_migrations (version) values ('$version')");
    }

    private function getVersion() {
        $db = api_database::factory();
        if ($stmt = $db->query("SELECT * FROM schema_migrations order by version DESC")) {
            $v = $stmt->fetch(PDO::FETCH_ASSOC);
        } else { 
            $db->query("CREATE TABLE schema_migrations (version character(14))");
            $v['version'] = 0;
        }
        return $v['version'];
    }

}

