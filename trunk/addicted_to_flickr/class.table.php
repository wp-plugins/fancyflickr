<?php
/**
* Object-Oriented Table Wrapper
* @author John Nunemaker <nunemaker@gmail.com>
* @link http://addictedtonew.com/archives/82/php-and-mysql-table-wrapper/
* @version 1.0
* @access public
*/
class table {
	/**
	* Instance of mysql class
	* @access public
	* @var mysql
	*/
	var $db;
	
	/**
	* The table this class is wrapping
	* @access public
	* @var string
	*/
	var $table 			= NULL;
	
	/**
	* The database field name of the primary key
	* @access public
	* @var integer
	*/
	var $primary_key 	= NULL;
	
	/**
	* The primary key value; only used if using find() method
	* @access public
	* @var integer
	*/
	var $id 			= NULL;
	
	/**
	* The database field name of the primary key
	* @access public
	* @var integer
	*/
	var $debug 			= false;
	
	/**
	* An array of all the database field names
	* @access public
	* @var array
	*/
	var $fields 		= array();
	
	/**
    * table constructor
	*
    * @param mysql $db (MySQL database object)
    * @param string $table (the name of the table to be wrapped)
    * @access public
    */
	function table(& $db, $table = NULL) {
		$this->db 		=& $db;
		$this->table 	= $table;
		
		if ($this->table == NULL) {
			if ($this->debug) {
				echo '<p><strong style="color:red">Error:</strong> A table must be supplied as an argument to the contructor.</p>';
			}
			return false;
		}
		
		$this->clear();
	}
	
	/**
    * Clears out class and resets it to default values
	*
    * @access public
    */
	function clear() {
		$this->fields 		= array();
		$this->id 			= NULL;
		$this->primary_key 	= NULL;
		
		// describe the table in a query result
		$sql = "DESCRIBE $this->table";
		$result = $this->db->query($sql);
		
		// loop through all the columns
		while ($row = $result->fetchRow()) {
			// set object variables = default value in database
			$this->$row['Field'] = $row['Default'];
			
			// add field to fields array
			array_push($this->fields, $row['Field']);
			
			// set primary key
			if ($row['Key'] == 'PRI') {
				$this->primary_key = $row['Field'];
			}
		}
	}
	
	/**
	* Sets debug to true or false; true shows errors false hides them
	*
	* @access public
	*/
	function setDebug($val) {
		$this->debug = $val;
	}
	
	/**
    * Sets all the class variables based on a primary key
	*
    * @param integer $id (the id of the row you would like access to)
    * @access public
	* @return bool (true on success; false on record on found)
    */
	function find($id) {
		$sql = "SELECT * FROM $this->table WHERE $this->primary_key = $id LIMIT 1";
		if ($this->debug) {
			echo '<p><strong>Find SQL:</strong> ' . $sql . '</p>';
			if ($this->db->isError()) {
				echo $this->db->getErrorMsg();
				return false;
			}
		}
		$result = $this->db->query($sql);
		
		if ($result->numRows() > 0) {
			$this->id = $id;
			
			// found - set all class variables to row values
			$row = $result->fetchRow();
			foreach($row as $field_name => $field_value) {
				$this->$field_name = $field_value;
			}
			return true;
		} else {
			// could not be found
			if ($this->debug) {
				echo "<p><strong style=\"color:red\">Error:</strong> The primary key value ($this->id) could not be found in the database.</p>";
			}
			return false;
		}
	}
	
	/**
    * Returns an array of table objects each set to there id
	*
    * @param string $where (sql where clause to limit which rows are fetched)
    * @param string $orderby (sql order by clause to order the result records)
	* @param string $other (any other sql you would like to tack on the end)
	* @return array or bool (array on success, bool on none found)
    * @access public
    */
	function findMany($where = '', $orderby = '', $other = '') {
		$all = array();
		
		$sql = "SELECT $this->primary_key FROM $this->table $where $orderby $other";
		if ($this->debug) {
			echo '<p><strong>Find Many SQL:</strong> ' . $sql . '</p>';
			if ($this->db->isError()) {
				echo $this->db->getErrorMsg();
				return false;
			}
		}
		$result = $this->db->query($sql);
		
		if ($result->numRows() > 0) {
			while ($row = $result->fetchRow()) {
				$t =& new table($this->db, $this->table);
				$t->find($row[$this->primary_key]);
				$all[] = $t;
			}
			return $all;
		} else {
			if ($this->debug) {
				echo "<p>No results were found based on the criteria you provided. (where = $where // orderby = $orderby)</p>";
			}
			return false;
		}
	}
	
	/**
    * Updates a single attribute or an array of attributes of a record in the table
	*
    * @param array $attr (attribute(s) to be updated)
	* @param integer $id (optional - if not provided then a $table->find($id) must first be called)
    * @access public
    */
	function updateAttr($attr, $id = NULL) {
		// if $id is not null, then set the variable id to it and 
		// set all the other class variables with a find() call
		if ($id != NULL) {
			$this->id = $id;
			$this->find($id);
		}
		
		// if its an array then loop through and create the sql
		if (is_array($attr)) {
			$sql = "UPDATE $this->table SET ";
			foreach($attr as $key => $val) {
				// under no circumstance update the primary key
				if ($this->primary_key != $attr) {
					$sql .= "$key = '$val',";
					$this->$key = $val;
				}
			}
			$sql = substr($sql, 0, strlen($sql) - 1); // remove trailing comma
			$sql .= " WHERE $this->primary_key = $this->id";
			$result = $this->db->query($sql);
			if ($this->debug) {
				echo '<p><strong>Update Attr SQL:</strong> ' . $sql . '</p>';
				if ($this->db->isError()) {
					echo $this->db->getErrorMsg();
					return false;
				}
			}
			return true;
		
		// else it won't work so return false
		} else {
			if ($this->debug) {
				echo '<p><strong style="color:red">Error:</strong> The function argument $attr must be an array. What you passed is not.</p>';
			}
			return false;
		}
	}
	
	/**
    * Inserts or updates a row in the table
	*
    * @access public
    */
	function save() {
		if ($this->id == NULL) {
			// there hasn't been a find so we are inserting
			$sql = "INSERT INTO $this->table (" . implode(',', $this->fields) . ") VALUES ('',";
			
			foreach($this->fields as $field) {
				if ($field != $this->primary_key) {
					$sql .= "'" . $this->$field . "',";
				}
			}
			
			// remove trailing comma
			$sql = substr($sql, 0, strlen($sql) - 1);
			$sql .= ")";
			$result = $this->db->query($sql);
			if ($this->debug) {
				echo '<p><strong>Insert SQL:</strong> ' . $sql . '</p>';
				if ($this->db->isError()) {
					echo $this->db->getErrorMsg();
					return false;
				}
			}
			
			return true;
		} else {
			// update
			$sql = "UPDATE $this->table SET ";
			
			foreach($this->fields as $field) {
				if ($field != $this->primary_key) {
					$sql .= "$field='" . $this->$field . "' ,";
				}
			}
			// remove trailing comma
			$sql = substr($sql, 0, strlen($sql) - 1);
			$sql .= " WHERE $this->primary_key = $this->id LIMIT 1";
			$result = $this->db->query($sql);
			if ($this->debug) {
				echo '<p><strong>Update SQL:</strong> ' . $sql . '</p>';
				if ($this->db->isError()) {
					echo $this->db->getErrorMsg();
					return false;
				}
			}
			
			return true;
		}
	}
	
	/**
    * Destroys a record in the table
	*
    * @param integer $id ()
    * @access public
    */
	function destroy($id) {
		$sql = "DELETE FROM $this->table WHERE $this->primary_key = $id";
		$result = $this->db->query($sql);
		if ($this->debug) {
			echo '<p><strong>Destroy SQL:</strong> ' . $sql . '</p>';
			if ($this->db->isError()) {
				echo $this->db->getErrorMsg();
				return false;
			}
		}
		
		return true;
	}
	
	/**
	* Dumps the current object information; only use in test
	* environment as this will display private information; this
	* is great for debugging and learning how the class functions
	*
	* @access public
	*/
	function dump() {
		echo '<pre>';
		echo '<h3>Object Dump</h3>';
		print_r($this);
		echo '</pre>';
	}
}
?>