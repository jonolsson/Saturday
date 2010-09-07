<?php
class api_database_pdoext extends PDO {

    protected $logger = null;

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
 //       $cfg = api_config::getInstance();
 //       $writerConfig = $cfg->log;
 //       $this->logger = Zend_Log::factory(array($writerConfig));
        //$db = new PDO( $dsn, $config['username'], $config['password'] );
            $cfg = api_config::getInstance();
            $this->logger = Zend_Log::factory(array($cfg->log));
        parent::__construct($dsn, $config['username'], $config['password']);
    }

    public function getConnection() {
        return $this;
    
    }

    public function query($statement) {
        $this->logger->info($statement instanceOf pdoext_Query ? $statement->toSql($this) : $statement);
        return parent::query($statement);
        //$this->log($statement instanceOf pdoext_Query ? $statement->toSql($this) : $statement));
    }
    
}
