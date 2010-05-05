<?php
abstract class database_Adapter {
    protected $nameClosing;
    protected $nameOpeningh
    
    protected $config = array();

    protected $connection = null;

    public function __construct( $config ) {
        //echo "database Base constructor";
        switch ($config['driver']) {
            case 'mysql':
                $this->nameOpening = $this->nameClosing = '`';
                break;

            case 'mssql':
                $this->nameOpening = '[';
                $this->nameClosing = ']';
                break;

            default:
                $this->nameOpening = $this->nameClosing = '"';
                break;
        }

    }

    public function quoteName( $name ) {
        echo $this->nameOpening;
        return $this->nameOpening
            .str_replace( $this->nameClosing, $this->nameClosing.$this->nnameClosing, $name )
            .$this->nameClosing;
    }

    public function getConnection() {
        echo "Get connection";
        $this->connect(); 
        return $this;//$this->connection;
    }

    function query( $qry ) {
        //$this->connection->query
        echo "querying";
    }

}
