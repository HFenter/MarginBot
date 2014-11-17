<?php
class Database{
	
	var $database_name;
	var $database_user;
	var $database_pass;
	var $database_host;    
	var $database_link;
	
    function Database(){
    	global $config;
    	//print_r($config);
        $this->database_user = $config['db']['dbuser'];
        $this->database_pass = $config['db']['dbpass'];
        $this->database_host = $config['db']['host'];
        $this->database_name = $config['db']['dbname'];
        $this->connect();
    }
    
	function connect(){
		$this->database_link = new mysqli($this->database_host, $this->database_user, $this->database_pass, $this->database_name);
		
		if ($this->database_link->connect_errno) {
    		printf("Could not connect to Database: %s\n", $this->database_link->connect_error);
    		exit();
		}
		//or die("Could not connect to MySQL<br>Error: ".mysql_error());
		//$this->database_link = mysql_connect($this->database_host, $this->database_user, $this->database_pass) or die("Could not connect to MySQL<br>Error: ".mysql_error());
		//mysql_select_db($this->database_name) or die ("Could not open database: ". $this->database_name);        
	}
	function escapeStr($str){
		return $this->database_link->real_escape_string($str);
	}
	
	function disconnect(){
        if(isset($this->database_link)) $this->database_link->close();
        //else mysql_close();    
    }
    
	// Update / Delete / Insert
    function iquery($qry){
        if(!isset($this->database_link)) $this->connect();
		if ( ($result = $this->database_link->query($qry))===false )
		{
		  if($this->database_link->errno != 1062){
			  $this->DBError($qry,$this->database_link->errno, $this->database_link->error);
			  exit();
		  }
		}
        $return[num] = $this->database_link->affected_rows;
        $return[id] = $this->database_link->insert_id;
		//$this->database_link->close();
        return $return;
    }
    // select
	function query($qry){
        if(!isset($this->database_link)) $this->connect();
		if ( ($result = $this->database_link->query($qry))===false )
		{
		  if($this->database_link->errno != 1062){
			  $this->DBError($qry,$this->database_link->errno, $this->database_link->error);
			  exit();
		  }
		}
        $returnArray = array();
        $i=0;
		
		while ($row = $result->fetch_array(MYSQLI_ASSOC))
		{
		  $returnArray[$i++]=$row;
		}
		//$result->close();
        return $returnArray;
    }
    
	function DBError($query, $errno, $error, $user = 0) { 
		if(!isset($this->database_link)) $this->connect();
		$SQLDBE = "INSERT into SQLErrors (`query`, `page`,`user`, `date` ) VALUES('".$this->escapeStr($query)."','".$this->escapeStr($_SERVER["REQUEST_URI"])."', '".$this->escapeStr($user)."', NOW() )" ;
		
		
		$result = $this->database_link->query($SQLDBE);
        $errorId = $this->database_link->insert_id;
		//$result->close();
        die('<b>THERE HAS BEEN A DATABASE ERROR</b><br>Please <a href="'.$config['admin_email'].'?subject=DB Error: '.$errorId.'">Contact Support</a> If this error persists.<br><small>' . $errno . ' - ' . $error . '<br></small>');
		}
    
}

?>