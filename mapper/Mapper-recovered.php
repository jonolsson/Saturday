<?php

abstract class Mapper {
  protected static $DB;
  protected $nameOpening;
  protected $nameClosing;
  protected $table;

  function __construct( $table ) {
    if ( ! self::$DB ) {
      	self::$DB = new PDO('mysql:dbname=mapper;host=localhost', 'mapper', 'mapper' );
        self::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$DB->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        
        $this->table = $table;
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
        $this->selectStmt           = "SELECT * FROM ". $this->tabel." WHERE id=?";
        $this->selectAllStmt        = "SELECT * FROM $this->table";
        $this->updateStmt           = "UPDATE $this->table SET name=?, body=?, author_id=? WHERE id=?";
        $this->insertStmt           = "INSERT INTO $this->table
                                        (name, body, author_id)
                                        VALUES (?, ?, ?)";
        $this->deleteStmt           = "DELETE FROM $this->table WHERE id = ?";

    }
  }
  
    private function quoteName( $name ) {
        return $nameOpening
            .str_replace($nameClosing, $nameClosing.$nameClosing, $name)
            .$nameClosing;
    }

    private function getTableMeta() {
        $result = $pdo->query("SHOW COLUMNS FROM ".quoteName($table));
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $meta = Array();
        foreach ($result as $row) {
            $meta[$row['field']] = Array(
            'pk' => $row['key'] == 'PRI',
            'type' => $row['type'],
            );
        }
        print_r( $meta );
        return $meta;
    }
  private function ensure( $expr, $message ) {
    if ( ! $expr ) {
      echo "error";
    }
  }
  
  function DB() {
    $dsn = base_application_registry::getDSN();
    $this->ensure( $dsn, "No DSN" );
    if ( ! $this->db ) {
      $this->db = MDB2::connect( $dsn );
    }
    $this->ensure( (! MDB2::isError( $this->db )), "Unable to connect to DB" );
    return $this->db;
  }
  
  function load( $result ) {
    $array = $result->fetchRow();
    if ( ! is_array( $array ) ) { return null; }
    //if ( ! $array['id'] ) { return null; }
    $object = $this->loadArray( $array );
    return $object;
  }
  
  function loadArray( $array ) {
    //$obj = $this->doLoad ( $array );
    $obj = New stdClass;
    $meta = $this->getTableMeta();
    foreach( $meat as $key => $value ) {
        $obj->$key = $value;
    }
    return $obj;
  }
  
    function find( $id ) {
        $result = $this->doStatement( $this->selectStmt, array( $id ) );
        return $this->load( $result );
    }
  
  protected function doStatement( $sth, $values ) {
   
    echo 'Statement: ',$sth, "\n";
    echo 'values: ';
    print_r( $values );
    echo '\n\n';
    $stmt = self::$DB->prepare( $sth );
    $stmt->execute( $values );
    $db_result = $stmt->fetchRow();
    // error checking
    if ( ! $db_result ) {
      echo self::$DB->ErrorMsg();
    }
    return $db_result;
  }
  
  abstract function insert( $object );
  abstract function update( $object );
  protected abstract function doLoad( $array );
  protected abstract function doFind( $id );
}
