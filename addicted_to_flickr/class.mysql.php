<?php
/**
* MySQL Database Connection Class
* @access public
* @version 1.0
*/
class MySQL {
    /**
    * MySQL server hostname
    * @access private
    * @var string
    */
    var $host;

    /**
    * MySQL username
    * @access private
    * @var string
    */
    var $dbUser;

    /**
    * MySQL user's password
    * @access private
    * @var string
    */
    var $dbPass;

    /**
    * Name of database to use
    * @access private
    * @var string
    */
    var $dbName;

    /**
    * MySQL Resource link identifier stored here
    * @access private
    * @var string
    */
    var $dbConn;

    /**
    * Stores error messages for connection errors
    * @access private
    * @var string
    */
    var $connectError;
	
	/**
	* Stores the error messages
	* @access private
	* @var string
	*/
	var $errorMsg;

    /**
    * MySQL constructor
	*
    * @param string host (MySQL server hostname)
    * @param string dbUser (MySQL User Name)
    * @param string dbPass (MySQL User Password)
    * @param string dbName (Database to select)
    * @access public
    */
    function MySQL($host,$dbUser,$dbPass,$dbName) {
        $this->host 	= $host;
        $this->dbUser 	= $dbUser;
        $this->dbPass	= $dbPass;
        $this->dbName	= $dbName;
        $this->connect();
    }

    /**
    * Establishes connection to MySQL and selects a database
	*
    * @return void
    * @access private
    */
    function connect() {
        // Make connection to MySQL server
        if (!$this->dbConn = @mysql_connect($this->host, $this->dbUser, $this->dbPass)) {
            $this->errorMsg = 'Could not connect to server';
            $this->connectError = true;
        // Select database
        } else if ( !@mysql_select_db($this->dbName,$this->dbConn) ) {
            $this->errorMsg = 'Could not select database';
            $this->connectError = true;
        }
    }

    /**
    * Checks for MySQL errors
	*
    * @return boolean
    * @access public
    */
    function isError() {
        if ( $this->connectError ) { return true; }
        $this->errorMsg = mysql_error($this->dbConn);
        
		if ( empty($this->errorMsg) ) {
            return false;
		} else {
            return true;
		}
    }
	
	/**
	* Returns the error message if there is one set
	*
	* @return string
	* @access public
	*/
	function getErrorMsg() {
		return '<p style="color:red">' . $this->errorMsg . '</p>';
	}

    /**
    * Returns an instance of MySQLResult to fetch rows with
	*
    * @param $sql string (the database query to run)
    * @return MySQLResult
    * @access public
    */
    function & query($sql) {
        if (!$q = @mysql_query($sql,$this->dbConn)) {
            $this->errorMsg = 'Query failed: ' . mysql_error($this->dbConn) . ' SQL: ' . $sql;
		}
        return new MySQLResult($this,$q);
    }
	
	/**
	* Returns first column in the first row of query; 
	* handy for getting names, titles, counts and such
	*
	* @param $sql string (the database query to run)
	* @return string
	*/
	function getOne($sql) {
		if (!$q = @mysql_query($sql, $this->dbConn)) {
            $this->errorMsg = 'Query failed: ' . mysql_error($this->dbConn) . ' SQL: ' . $sql;
		}
		return @mysql_result($q, 0, 0);
	}
}

/**
* MySQLResult Data Fetching Class
* @access public
*/
class MySQLResult {
    /**
    * Instance of MySQL providing database connection
    * @access private
    * @var mysql
    */
    var $mysql;

    /**
    * Query resource
    * @access private
    * @var resource
    */
    var $query;

    /**
    * MySQLResult constructor
	*
    * @param object mysql   (instance of MySQL class)
    * @param resource query (MySQL query resource)
    * @access public
    */
    function MySQLResult(& $mysql,$query) {
        $this->mysql=& $mysql;
        $this->query=$query;
    }

    /**
    * Fetches a row from the result
	*
    * @return array
    * @access public
    */
    function fetchRow() {
        if ( $row = @mysql_fetch_array($this->query,MYSQL_ASSOC) ) {
            return $row;
        } else if ( $this->numRows() > 0 ) {
            @mysql_data_seek($this->query,0);
            return false;
        } else {
            return false;
        }
    }

    /**
    * Returns the number of rows selected
	*
    * @return int
    * @access public
    */
    function numRows() {
        return @mysql_num_rows($this->query);
    }

    /**
    * Returns the ID of the last row inserted
	*
    * @return int
    * @access public
    */
    function insertID() {
        return @mysql_insert_id($this->mysql->dbConn);
    }
    
    /**
    * Checks for MySQL errors
	*
    * @return boolean
    * @access public
    */
    function isError() {
        return $this->mysql->isError();
    }
}
?>