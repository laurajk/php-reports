<?php

// riferimenti: http://www.w3schools.com/php/php_mysql_connect.aspysql

class dbs {
	
	private $host;
	private $user;
	private $pass;
	private $name;
	private $port;
	
	// l'oggetto connessione
	public $obj = null;
	
	public function __construct($host, $user, $pass, $name, $port, $type) {
		
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->name = $name;
		$this->port = $port;
		$this->type = $type;
		
		//$this->obj = new pdo($this->host, $this->user, $this->pass, $this->name, $this->port);
		try{
			// create a PostgreSQL database connection
			$dsn="$this->type:dbname=$this->name; 
	                           host=$this->host; 
	                           user=$this->user; 
	                           password=$this->pass";
	        echo $dsn;	
			$this->obj = new PDO($dsn);
		 	$this->obj->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		 	// display a message if connected to the PostgreSQL successfully
			if($this->obj){
				echo "\nConnected to the $this->name database successfully!";
			}else{
				echo "\nConnected to the $this->name database ko!";	
			}
		}catch (PDOException $e){
			// report error message
			echo "Errore trovato: ".$e->getMessage();
			die();
		}

				
	}

	public function close(){
		$this->obj = null;
	}
	
	// OK per PDO
	// executes a query defined by $sql string
	public function query($sql) {
		
		$r = $this->obj->query($sql);
			
		if (!$this->obj) { 
			echo "db error <br>";
			echo $sql;
			print_r($this->obj->errorInfo()); 
			//$db->close();
			exit();
		} else {
			return $r;
		}

	}
	
	// OK per PDO
	// executes a list of queries included in array $sql_array
	public function do_queries($sql_array) {
		foreach($sql_array as $sql)	{
			$res = $this->query($sql);
			if(!$res)	return false;
		}
		return true;
	}
	
	// retrieve a list of columns present in db table $table
 	public function getColumnFields($table) {	
		$fields = array();
	
		$sql = sprintf("SHOW COLUMNS FROM %s", $this->res($table));
		
		$res = $this->query($sql);
		$rs = array();
		if($res && $res->num_rows) { 
			while($d = $res->fetch_assoc()) { $rs[] = $d; }
		}

		return $rs;
	}

 	// creates a sql query string to insert $data array in table $table
	public function prepareInsert($table, $data, $ignore = false) {

		if ((is_array($data) && empty($data)) || !is_array($data)) { return false; exit(); }

		$fields = $this->getColumnFields($table);
		$ign = ($ignore) ? 'IGNORE ' : '';
		$insert = array();

		foreach ($fields as $f) {
			$key = $f['Field'];

			if (in_array($key, array_keys($data))) {
				$value = $data[$key];				
				$hasNull = ($f['Null'] == 'YES') ? true : false;
				if (!is_numeric($value) && (is_null($value) || $value == 'NULL') && $hasNull) { $v = 'NULL'; }
				else {
					$type = (strpos($f['Type'], "(") > 0) ? substr($f['Type'], 0, strpos($f['Type'], "(")) : $f['Type'];
					switch (true) {
						case (in_array($type, array('int', 'bigint', 'tinyint'))): $v = ($value > 0) ? $this->bigint($value) : $value; break;
						case ($type == 'tinyint'): $v = ((bool)$value) ? 1 : 0; break;
						case ($type == 'decimal'): $v = str_replace(',', '.', $value); break;
						default: $v = "'".$this->res($value)."'";
					}
					if (!is_numeric($v) && (is_null($v) || $v == 'NULL') && $hasNull) $v = 'NULL';
				}
				$insert[$f['Field']] = $v;
			}
		}
		
		if (empty($insert)) { return false; exit(); }

		$sql = "INSERT ".$ign."INTO ".$this->res($table)." (".implode(", ",array_keys($insert)).") ";
		$sql .= " VALUES (".implode(", ",array_values($insert)).")";

		return $sql;

	}
	
	// creates a sql query string to update $data array in table $table with where conditions defined by $where array
	public function prepareUpdate($table, $data, $where) {

		if ((is_array($data) && empty($data)) || !is_array($data)) { return false; exit(); }

		$fields = $this->getColumnFields($table, $this);
				
		$insert = array();
				
		foreach ($fields as $f) {
			
			$key = $f['Field'];
			
			if ($f['Default'] == 'CURRENT_TIMESTAMP') { unset($data[$f['Field']]); }
			if (in_array($key, array_keys($data))) {
				$value = $data[$key];
				$hasNull = ($f['Null'] == 'YES') ? true : false;
				if (!is_numeric($value) && (is_null($value) || $value === 'NULL') && $hasNull) {  $v = 'NULL'; }
				else {
					$type = (strpos($f['Type'], "(") > 0) ? substr($f['Type'], 0, strpos($f['Type'], "(")) : $f['Type'];
					switch (true) {
						case (in_array($type, array('int', 'bigint', 'tinyint'))): $v = ($value > 0) ? $this->bigint($value) : $value; break;
						case ($type == 'tinyint'): $v = ((bool)$value) ? 1 : 0; break;
						case ($type == 'decimal'): $v = str_replace(',', '.', $value); break;
						default: $v = "'".$this->res($value)."'";
					}
					if (!is_numeric($v) && (is_null($v) || $v == 'NULL') && $hasNull) $v = 'NULL';
				}
				$insert[$f['Field']] = $v;
			}
		}

		if (empty($insert)) { return true; exit(); }

		$sql = "UPDATE ".$this->res($table).' SET ';		
		foreach ($insert as $k=>$v) { $sql .= sprintf("%s = %s, ", $k, $v);	}
		$sql = substr($sql,0,-2);
		$sql .= ' WHERE 1';
		
		foreach ($where as $k=>$v) {
			$sql .= sprintf(" AND %s = '%s'", $k, $v);
		}
		
		return $sql;	

	}
	
	// OK per PDO
	// puts select query result $rs in an array
	public function result2array_google($rs) {
		$ret = array();

		if ($rs->rowCount()) {
			while ($d = $rs->fetchAll()){	
				for($i=0;$i<count($d);$i++){
					for($y=0;$y<count($d[$i])/2;$y++){
						$ret[$i][$y] = $d[$i][$y];
					}
				}
			}
		
		}

		return $ret;
	}

	// OK per PDO
	// puts select query result $rs in an array
	public function result2array($rs) {
		$ret = array();
		if ($rs->rowCount()) {
			while ($d = $rs->fetch(PDO::FETCH_ASSOC)){
				$ret[] = $d->value;
			}
									
		}

		return $ret;
	}
	
	// cleans up string $string formatting it to sql query language
	public function res($string, $trailingQuote = false) {

		if (is_array($string)) {
			foreach ($string as $key => $single_string) {
				$string[$key] = $this->res($single_string);
			}
			return $string;
		}

		if (is_null($string) && $trailingQuote) {
			return 'NULL';
		} else {
			return ($trailingQuote) ? "'".$this->obj->real_escape_string($string)."'" : $this->obj->real_escape_string($string);
		}
	}
	
	// retrieve the id of the element inserted by last sql query
	public function insert_id() {
		return $this->obj->insert_id;
	}
	
	// retrieve the list of rows affected by last sql query
	public function affected_rows() {
		return $this->obj->affected_rows;
	}	

 	// checks if $number is composed only by other ciphers
	public function bigint($number) {
		if (!is_null($number) && is_numeric($number)) {
			$out = preg_replace('/[^0-9]/','',$number);
			return $out*1;
		} else {
			return false;
		}
	}
	
	
}