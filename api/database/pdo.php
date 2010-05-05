<?php
class database_pdo extends PDO {
    
    public function __construct()   {
        if (! extension_loaded ( 'pdo' )) {
            throw new api_exception(api_exception::THROW_NONE, null, null, 'PDO Extension not installed');
        }
    }

    public function getConnection( $config ) {
         if ( ! $config ) {
            throw new Exception("No config");
        }
        if ($config['driver'] == "mysql") {
            $dsn = $config['driver'].":host=".$config['host'].";dbname=".$config['database'];
        } else if ($config['driver'] == "postgres") {
            $dsn = "pgsql:host=".$config['host'].";dbname=".$config['database'].";user=".$config['username'].";password=".$config['password'];
        }
        $db = new PDO( $dsn, $config['username'], $config['password'] );
        return $db;
    }
}
