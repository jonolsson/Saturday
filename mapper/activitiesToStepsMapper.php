<?php
class mcms_activitiesToStepsMapper extends mcms_mapper {
  private $selectStmt;
  private $selectAllStmt;
  private $updateStmt;
  private $insertStmt;
  private $deleteStmt;
  
  function __construct() {
    parent::__construct();
    $this->selectStmt           = "SELECT * FROM tbl_activity_to_steps WHERE atsId=?";
    $this->selectAllStmt        = "SELECT * FROM tbl_activity_to_steps";
    $this->updateStmt           = "UPDATE tbl_activity_to_steps SET tsId=?, tId=?, tsNumber=?, tsName=?, tsSide=?, tsReps=?, tsMuscle=? WHERE tid=?";
    $this->insertStmt           = "INSERT INTO tbl_activity_to_steps
                                    (tId, tsNumber, tsName, tsSide, tsReps, tsMuscle)
                                    VALUES (?, ?, ?, ?, ?, ?)";
    $this->deleteStmt           = "DELETE FROM tbl_activity_to_steps WHERE tId = ?";
  }

  function doFind( $id ) {
    $result = $this->doStatement( $this->selectStmt, array( $id ) );
    return $this->load( $result );
    return $name;
  }
  
  function findAll( ) {
      $result = $this->doStatement( $this->selectAllStmt, array() );
      return new activitiesToStepsMapperCollection( $result, $this );
  }
  
  protected function doLoad( $array ) {
    $obj = new mcms_activitiesToSteps();
    $obj->id		= $array['atsId'];
    $obj->name          = $array['atsName'];
    $obj->factor        = $array['atsFactor'];
    return $obj;
  }
  
  function insert( $object ) {
    $values = array(  $object->trainingId
                    , $object->nr
                    , $object->name
                    , $object->side
                   
                    , $object->reps
                    , $object->muscle
                    );
    $result = $this->doStatement( $this->insertStmt, $values );
    $object->id = $this->newId();
    //$result->close();
  }
  
  public function newId() {
    return self::$DB->Insert_Id();
  }
  
  function update( $object ) {
    $values = array( $object->nr
                    , $object->name
                    , $object->side
                    , $object->reps
                    , $object->tsid
                    , $object->stretch_nr
                    , $object->tid
                    , $object->muscle
                    , $object->length );
    $this->doStatement( $this->updateStmt, $values );
  }
  
  function delete($object) {
    $values = array( $object->tid );
    $this->doStatement( $this->deleteStmt, $values );
  }
}

class activitiesToStepsMapperCollection extends mcms_mapperCollection {
  function add( $object ) {
    $this->doAdd( $object );
  }
}

?>