<?php

abstract class Mapper {
    protected static $DB;
    protected $nameOpening;
    protected $nameClosing;
    protected $primary_key = "id";
    protected $table;
    protected $driver = '';

    private $tableMeta = array();
    private $metaMinusPK = array();

    function __construct( $table ) {
        if ( ! self::$DB ) {
      	    self::$DB = new PDO('mysql:dbname=mapper;host=localhost', 'mapper', 'mapper' );
            self::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$DB->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        
            $this->table = $table;
            $this->driver = api_config::getInstance()->database-Adefault->driver;

            switch (self::$DB->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                $nameOpening = $nameClosing = '`';
                break;

            case 'mssql':
                $nameOpening = '[';
                $nameClosing = ']';
                break;

            default:
                $nameOpening = $nameClosing = '"';
                break;
            }

            $this->tableMeta = $this->getTableMeta();
            $this->metaMinusPK = $this->tableMeta;
            unset( $this->metaMinusPK[$this->primary_key]);

            print_r($this->metaMinusPK);

            $this->selectStmt           = "SELECT * FROM ". $this->table." WHERE $this->primary_key=?";
            $this->selectAllStmt        = "SELECT * FROM $this->table";
            $this->updateStmt           = $this->buildUpdateStmt(); 
            $this->insertStmt           = $this->buildInsertStmt(); 
            $this->deleteStmt           = "DELETE FROM $this->table WHERE id = ?";

        }
    }
    
    static private function prepareUpdate( $str ) {
        return $str . "=?";
    }

    private function buildUpdateStmt() {
        $columns = array_keys($this->metaMinusPK);
        $c = array_map( array("Mapper", "prepareUpdate"), $columns );
        $stmt = "UPDATE $this->table SET ";
        $stmt .= implode( ",", $c );
        $stmt .= " WHERE $this->primary_key=?";
        return $stmt;
    }

    private function buildInsertStmt() {
        echo "Build update statement\n";
        $columns = array_fill(0, count($this->metaMinusPK), "?");
        $stmt = "INSERT INTO $this->table (";
        $stmt .= implode( ",", array_keys($this->metaMinusPK) );
        
        $stmt .= ") VALUES (";
        $stmt .=implode(",", $columns); 
        $stmt .= ")";
        echo $stmt;
        return $stmt;
    }
  
    private function quoteName( $name ) {
        return $nameOpening
            .str_replace($nameClosing, $nameClosing.$nameClosing, $name)
            .$nameClosing;
    }

    private function getTableMeta() {

        if ($this->driver == "postgres") {
            $q = "SELECT column_name as field, data_type as type
                    FROM information_schema.columns
                    WHERE table_name = '".$this->table."'";
        } else {         
            $result = self::$DB->query("SHOW COLUMNS FROM ".$this->quoteName($this->table));
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $meta = Array();
        foreach ($result as $row) {
            $meta[$row['field']] = Array(
            //'pk' => $row['key'] == 'PRI',
            'type' => $row['type'],
            );
        }
        return $meta;
    }
    
    private function ensure( $expr, $message ) {
        if ( ! $expr ) {
            echo "error";
        }
    }

    /**
      Get id from last insert
    */
    protected function newId() {
        return self::$DB->lastInsertId();
    }

    /**
      Get the name of the primary key column
    */
    protected function getPrimaryKey() {
        return $this->primary_key;
    }
  
    protected function DB() {
        $dsn = base_application_registry::getDSN();
        $this->ensure( $dsn, "No DSN" );
        if ( ! $this->db ) {
            $this->db = MDB2::connect( $dsn );
        }
        $this->ensure( (! MDB2::isError( $this->db )), "Unable to connect to DB" );
        return $this->db;
    }
  
    protected function load( $result ) {
        $array = $result->fetch(PDO::FETCH_OBJ);
        return $array;
    }
  
    protected function loadArray( $array ) {
        return $obj;
    }
  
  
    protected function doStatement( $sth, $values ) {
   
        echo 'Statement: ',$sth, "\n";
        echo 'values: ';
        print_r( $values );
        echo "\n";
    
        $stmt = self::$DB->prepare( $sth );
        $stmt->execute( $values );
        if ( ! $stmt ) {
            echo self::$DB->ErrorMsg();
        }
        return $stmt;
    }
  
    /*
       exposed function
       create, read, update, delete
       read - finders
    */
    function find( $id ) {
        $result = $this->doStatement( $this->selectStmt, array( $id ) );
        return $this->load( $result );
    }

    function delete($id) {
        if (is_string($id)) {
            $result = $this->doStatement($this->deleteStmt, array($id));
        } else if (is_object($id)) {
            $pk = $this->getPrimaryKey();
            $id = $id->$pk;
            $result = $this->doStatement($this->deleteStmt, array($id));
        }
    }

    function insert( $object )  {
        $values = get_object_vars( $object );                    
        unset($values[$this->primary_key]);

        $result = $this->doStatement( $this->insertStmt, array_values( $values ) );
        $object->id = $this->newId();
        return $object;
    }

  function update( $object ) {
        $values = get_object_vars( $object );
        print_r( $values );
        $id = $values[$this->primary_key];
        unset( $values[$this->primary_key] );
        $values[$this->primary_key] = $id;
        $result = $this->doStatement( $this->updateStmt, array_values( $values ) );
  }
}
