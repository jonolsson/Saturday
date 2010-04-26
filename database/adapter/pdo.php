<?php

class database_adapter_Pdo extends database_adapter {
//    protected $nameOpening;
//    protected $nameClosing;

    public function __construct($config) {
        parent::__construct($config);
        if (! $config) {
            throw new Exception( $e );
        }
        
       
        
        //return $this;
    }

    /**
     * Quote names
     * @param $name to be quoted
     */
    public function quoteName( $name ) {
        return $this->nameOpening
            .str_replace( $this->nameClosing, $this->nameClosing.$this->nnameClosing, $name )
            .$this->nameClosing;
    }

    public function connect() {
        $dsn = "mysql:host=".$this->config['host'].";dbname=".$this->config['database'];
//        parent::__construct( $dsn, $config['username'], $config['password'] );
        $pdo = new PDO( $dsn, $this->config['username'], $this->config['password'] );
        //return $pdo;
        $this->connection = $pdo;
    }

    public function query( $sql ) {
        echo "Qvery";
        $stmt = $this->connection->prepare( $sql );
        print_r( $sql );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
