<?php

/**
 * Database factory
 * Produces a database connectioj (adapter)
 *
 */
class api_database {

    protected static $instances = array();

    /**
      Return a database connection
      
      @param $name string: name of the connection
      @param $force bool: force a new connection even if one already exists
      @return connection
    */
    public static function factory( $name = "default", $force = false ) {
        if ( isset( self::$instances[$name] ) && $force == false ) {
            return self::$instances[$name];
        }

        $db = api_config::getInstance()->database;
        if ( empty( $db[$name] ) ) {
            return false;
        }

        self::$instances[$name] = self::get( $db[$name] );
        return self::$instances[$name];
    }

    private static function get( $config ) {
        if ( empty( $config['database'] ) ) {
            
        }

        if (empty($config['adapter'])) {
            $config['adapter'] = "pdo";
        }

        $driver = "api_database_".$config['adapter'];
        $db = new $driver($config);
        return $db->getConnection($config);
    }

}
