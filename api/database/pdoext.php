<?php
class api_database_pdoext extends PDO {
    
    public function __construct($config)   {
        if (! extension_loaded ( 'pdo' )) {
            throw new api_exception(api_exception::THROW_NONE, null, null, 'PDO Extension not installed');
        }
        if ( ! $config ) {
            throw new Exception("No config");
        }
        if ($config['driver'] == "mysql") {
            $dsn = $config['driver'].":host=".$config['host'].";dbname=".$config['database'];
        } else if ($config['driver'] == "postgres") {
            $dsn = "pgsql:host=".$config['host'].";dbname=".$config['database'].";user=".$config['username'].";password=".$config['password'];
        }
        //$db = new PDO( $dsn, $config['username'], $config['password'] );
        parent::__construct($dsn, $config['username'], $config['password']);
    }

    public function getConnection() {
        return $this;
    
    }

    public function query($statement) {
        echo "Ext Query Jon";
        return parent::query($statement);
        //$this->log($statement instanceOf pdoext_Query ? $statement->toSql($this) : $statement));
    }
    
}
