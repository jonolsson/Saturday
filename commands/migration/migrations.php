<?php
class commands_migration_Migrations {
    
    /**
     * Platform - which platform to run migrations on
     */
    private $platform = "";

    /**
     * db - database connection
     */
    private $db = null;

    function __construct() {
        //$this->db = new Database("mysql:host=localhost;dbname=mapper", "mapper", "mapper");
        echo "\nCalling dDatabase::factory()\n";
        $this->db = Database::factory();
        echo "\nIn commands_migration_Migrations - constructor\n";
        print_r( $this->db );
        $this->platform = "mysql";
    }
   
    /**
     * Get Default Limit
     *
     * @param type
     */
    private function getDefaultLimit( $type ) {
        $default_limit = "";
        switch ( $type ) {

			case DECIMAL: $default_limit = "(10,0)"; break;
			case INTEGER: $default_limit = "(11)"; break;
			case STRING:  $default_limit = "(255)"; break;
			case BINARY:  $default_limit = "(1)"; break;
			case BOOLEAN: $default_limit = "(1)"; break;
			default: $default_limit = "";

		}
        return $default_limit;

    }

    /**
     * Get Datatype
     *
     * @param type
     */
    private function getDatatypes( $type ) {
        $new_type = "";
        switch ( $type ) {
            case INTEGER: $new_type = "INT"; break;
            case STRING: $new_type = "VARCHAR"; break;
            case BOOLEAN: $new_type = "TINYINT"; break;
        }
        return $new_type;
    }

// ------------------------------------------------------------------------

/**
 * Create Table
 *
 * Creates a new table
 *
 * $table_name:
 *
 * 		Name of the table to be created
 *
 * $fields:
 *
 * 		Associative array containing the name of the field as a key and the
 * 		value could be either a string indicating the type of the field, or an
 * 		array containing the field type at the first position and any optional
 * 		arguments the field might require in the remaining positions.
 * 		Refer to the TYPES function for valid type arguments.
 * 		Refer to the FIELD_ARGUMENTS function for valid optional arguments for a
 * 		field.
 *
 * $primary_keys:
 *
 * 		A string indicating the name of the field to be set as a unique primary
 * 		key or an array listing the fields to be set as a combined key.
 * 		If a field is selected as a primary key and its type is integer, it
 * 		will be set as an incremental field (auto_increment in mysql, serial in
 * 		postgre, etc).
 *
 * @example
 *
 *		create_table (
 * 			'blog',
 * 			array (
 * 				'id' => array ( INTEGER ),
 * 				'title' => array ( STRING, LIMIT, 50, DEFAULT, "The blog's title." ),
 * 				'date' => DATE,
 * 				'content' => TEXT
 * 			),
 * 			'id'
 * 		)
 *
 * @access	public
 * @param	string $table_name
 * @param	array $fields
 * @param	mixed $primary_keys
 * @return	boolean
 */ 
    protected function create_table( $table_name, $fields, $primary_keys = false ) {
        echo "In create table!";
        //$platform = "mysql";
        switch ( $this->platform ) {
        case 'mysql':
            if ( !empty($primary_keys) ) $primary_keys = (array)$primary_keys;
			$sql = "CREATE TABLE `{$table_name}` (";

			foreach ( $fields as $field_name => $params ) {

				$params = (array)$params;

				// Get the default Limit
                $default_limit = $this->getDefaultLimit( $params[0] );
				
                // Convert to mysql datatypes
                $params[0] = $this->getDatatypes( $params[0] );

                $sql .= "`{$field_name}` {$params[0]}";
				$sql .= in_array(LIMIT,$params,true) ? "(" . $params[array_search(LIMIT,$params,true) + 1] . ") " : $default_limit . " ";
				$sql .= in_array(DEFAULT_VALUE,$params,true) ? "default " . $CI->db->escape($params[array_search(DEFAULT_VALUE,$params,true) + 1]) . " " : "";
				$sql .= in_array(NOT_NULL,$params,true) ? "NOT NULL " : "NULL ";
				$sql .= in_array($field_name,$primary_keys,true) && $params[0] == INTEGER ? "auto_increment " : "";
				$sql .= ",";

			}

			$sql = rtrim($sql,',');

			if ( !empty($primary_keys) ) {

				$sql .= ",PRIMARY KEY (";

				foreach ( $primary_keys as $pk ) {
					$sql .= "`{$pk}`,";
				}

				$sql = rtrim($sql,',');
				$sql .= ")";

			}

			$sql .= ")";

		break;
    }

    // Execute query
    echo "\nSQL: "; 
    echo $sql;
    echo "\n";
    print_r( $this->db );
    
    $result = $this->db->query( $sql );
    print_r( $result );
}
    
// ----------------------------------------------------------------
/**
 * Rename a table
 *
 * @access public
 * @param string $old_name
 * @param string $new_name
 * @return boolean
 */
protected function rename_table($old_name, $new_name) {

	//$db =& _get_instance_w_dbutil();

	switch ( $this->platform ) {

		case 'mysql':
		default:

			$sql = "RENAME TABLE `{$old_name}`  TO `{$new_name}` ;";
			break;

	}
    
    echo "\nSQL: $sql\n";
	return $this->db->query($sql);

}
// ------------------------------------------------------------------------

/**
 * Drop a table
 *
 * @param string $table_name
 * @return boolean
 */
protected function drop_table($table_name) {

	return $this->db->query("DROP TABLE {$table_name}");

}

// ------------------------------------------------------------------------

    /**
    * Add a column to a table
    *
    * @example add_column ( "the_table", "the_field", STRING, array(LIMIT, 25, NOT_NULL) );
    * @access public
    * @param string $table_name
    * @param string $column_name
    * @param string $type
    * @param array $arguments
    * @return boolean
    */
    protected function add_column($table_name,$column_name,$type,$arguments=array()) {

	    //$CI =& _get_instance_w_dbutil();

	    switch ( $this->platform ) {

		    case 'mysql':
		    default:

			    $sql = "ALTER TABLE `{$table_name}` ADD `{$column_name}` {$type}";

			    // Get the default Limit

			    switch ( $type ) {

				    case DECIMAL: $default_limit = "(10,0)"; break;
				    case INTEGER: $default_limit = "(11)"; break;
				    case STRING:  $default_limit = "(255)"; break;
				    case BINARY:  $default_limit = "(1)"; break;
				    case BOOLEAN: $default_limit = "(1)"; break;
				    default: $default_limit = "";

			    }

			    $sql .= in_array(LIMIT,$arguments,true) ? "(" . $arguments[array_search(LIMIT,$arguments,true) + 1] . ") " : $default_limit . " ";
			    $sql .= in_array(DEFAULT_VALUE,$arguments,true) ? "default " . $CI->db->escape($arguments[array_search(DEFAULT_VALUE,$arguments,true) + 1]) . " " : "";
			    $sql .= in_array(NOT_NULL,$arguments,true) ? "NOT NULL " : "NULL ";
			    break;

	        }

	        return $this->db->query($sql);

        }

// ------------------------------------------------------------------------

    /**
    * Rename a column
    *
    * @access public
    * @param string $table_name
    * @param string $column_name
    * @param string $new_column_name
    */
    protected function rename_column($table_name, $column_name, $new_column_name) {

	    // TO DO

    }

    // ------------------------------------------------------------------------

    protected function change_column($table_name, $column_name, $type, $options) {

	    // TO DO

    }

    // ------------------------------------------------------------------------

    /**
    * Remove a column from a table
    *
    * @access public
    * @param string $table_name
    * @param string $column_name
    * @return boolean
    */
    protected function remove_column($table_name, $column_name) {

	    return $this->db->query("ALTER TABLE {$table_name} DROP COLUMN {$column_name}");

    }

// ------------------------------------------------------------------------

    protected function add_index($table_name, $column_name, $index_type) {

	    // TO DO

    }

// ------------------------------------------------------------------------

    protected function remove_index($table_name, $column_name) {

	    // TO DO

    }
}
