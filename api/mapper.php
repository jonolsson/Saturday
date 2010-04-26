<?php
class api_mapper {

protected static $DB;
    protected $name_opening;
    protected $name_closing;
    protected $primary_key = "id";
    protected $table;
    protected $driver = null;

    protected $model_prefix = "models";

    private $table_meta = array();
    private $table_meta_minus_pk = array();

    function __construct( $table ) {
        if ( ! isset(self::$DB) ) {
      	    //self::$DB = new PDO('mysql:dbname=mapper;host=localhost', 'mapper', 'mapper' );
            //
            self::$DB = database::factory();
            self::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$DB->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        }
        //echo "<br />self::DB => ";
        //$sth = self::$DB->exec("select * from user");
        //print_r(self::$DB->errorCode());
        //echo "<br />";
            $this->driver = self::$DB->getAttribute(PDO::ATTR_DRIVER_NAME);
            $this->table = $table;
        
            switch ($this->driver) {
            case 'mysql':
                $this->name_opening = $this->name_closing = '`';
                break;

            case 'mssql':
                $this->name_opening = '[';
                $this->name_closing = ']';
                break;

            case 'pgsql':
                $this->name_opening = "'";
                $this->name_closing = "'";
                break;

            default:
                $this->name_opening = $this->name_closing = '"';
                break;
            }

            $this->table_meta = $this->get_table_meta();
            $this->table_meta_minus_pk = $this->table_meta;
            unset( $this->table_meta_minus_pk[$this->primary_key]);

            $this->selectStmt           = "SELECT * FROM ". $this->table." WHERE $this->primary_key=?";
            $this->selectAllStmt        = "SELECT * FROM $this->table";
            $this->updateStmt           = $this->buildUpdateStmt(); 
            $this->insertStmt           = $this->buildInsertStmt(); 
            $this->deleteStmt           = "DELETE FROM $this->table WHERE id = ?";

      // }
            foreach($this->table_meta as $key => $value) {
                $this->$key = '';
            }
    }
    
    static private function prepareUpdate( $str ) {
        return $str . "=?";
    }

    private function buildUpdateStmt() {
        $columns = array_keys($this->table_meta_minus_pk);
        $c = array_map( array("api_mapper", "prepareUpdate"), $columns );
        $stmt = "UPDATE $this->table SET ";
        $stmt .= implode( ",", $c );
        $stmt .= " WHERE $this->primary_key=?";
        return $stmt;
    }

    private function buildInsertStmt() {
        //echo "Build update statement\n";
        $columns = array_fill(0, count($this->table_meta_minus_pk), "?");
        $stmt = "INSERT INTO $this->table (";
        $stmt .= implode( ",", array_keys($this->table_meta_minus_pk) );
        
        $stmt .= ") VALUES (";
        $stmt .=implode(",", $columns); 
        $stmt .= ")";
        //echo $stmt;
        return $stmt;
    }
  
    private function quoteName( $name ) {
        return $this->name_opening
            .str_replace($this->name_closing, $this->name_closing.$this->name_closing, $name)
            .$this->name_closing;
    }

    private function get_table_meta() {
        switch($this->driver) {
        case 'pgsql':
            $result = self::$DB->query("SELECT column_name as field, data_type as type FROM information_schema.columns WHERE table_name = ".$this->quoteName($this->table));
            $i = 0;
            $fields = array();

            $result->setFetchMode(PDO::FETCH_ASSOC);
            $meta = Array();
         
            foreach ($result as $row) {
                $meta[$row['field']] = Array(
                'type' => $row['type']);
                //print_r( $row);
                //echo "<br />";
            }
        
            break;
        default:
            $result = self::$DB->query("SHOW COLUMNS FROM ".$this->quoteName($this->table));
            $result->setFetchMode(PDO::FETCH_ASSOC);
            $meta = Array();
            foreach ($result as $row) {
                $meta[$row['Field']] = Array(
                    'pk' => $row['Key'] == 'PRI',
                    'type' => $row['Type'],
                );
            }
        }
        return $meta;
    }
    
    private function ensure( $expr, $message ) {
        if ( ! $expr ) {
            //echo "error";
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
        $array = $result->fetch(PDO::FETCH_ASSOC);
        // Create object
        $class = $this->model_prefix."_".$this->class;
        $obj = new $class();
        foreach($this->table_meta as $key=>$value) {
            $obj->$key = $array[$key];
        }

        return $obj;
    }
  
    protected function loadArray( $array ) {
        return $obj;
    }
  
  
    protected function doStatement( $sth, $values ) {
   
       // echo 'Statement: ',$sth, "\n";
       // echo 'values: ';
       // print_r( $values );
       // echo "\n";
    
        $stmt = self::$DB->prepare( $sth );
        $stmt->execute( $values );
        if ( ! $stmt ) {
            //echo self::$DB->ErrorMsg();
            // throw exception TODO
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

    function findAll() {
        $result = $this->doStatement( $this->selectAllStmt, array() );
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


    /* 
     */
    function insert($obj)  {
        
        //echo "insert";
        // Build insert statement on the fly!
        $columns = array_fill(0, count($this->table_meta_minus_pk), "?");
        $stmt = "INSERT INTO $this->table (";
        $stmt .= implode( ",", array_keys($this->table_meta_minus_pk) );
        
        $stmt .= ") VALUES (";
        $stmt .=implode(",", $columns); 
        $stmt .= ")";
        //echo $stmt;
        //return $stmt;

        $params = array();
        $values = get_object_vars( $this );                    
        //print_r($this->table_meta_minus_pk);
        unset($values[$this->primary_key]);

        foreach($this->table_meta_minus_pk as $key=>$value) {
            $params[] = $obj->$key;
        }
        //print_r($params);
        $result = $this->doStatement( $stmt, array_values( $params ) );
        $obj->id = $this->newId();
        return $obj;
    }

  function update( $obj ) {
        foreach($this->table_meta_minus_pk as $key=>$value) {
            $values[] = $obj->$key;
        }
        $pk = $this->primary_key;
        $id = $obj->$pk; //$this->primary_key;
        //echo "primary_key: ".$obj->id;
        unset( $values[$this->primary_key] );
        $values[$this->primary_key] = $id;
        $result = $this->doStatement( $this->updateStmt, array_values( $values ) );
  }

//    protected $db = null;
//    function __construct() {
//        $db = database::factory();
//    }
}
