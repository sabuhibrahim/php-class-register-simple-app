
<?php

class YeniSQL 
{

	protected $hostname = "localhost";

	protected $username = "root";

	protected $password = "";

	protected $db = "jurnal";

	static protected $conn;

	function __construct()
	{

		$table1 = "CREATE TABLE sagirdler (
					user_id INT AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$table2 = "CREATE TABLE fenns (
					fenn_id INT AUTO_INCREMENT PRIMARY KEY,
					user_id INT,
					KEY fn_user_idx (user_id),
					CONSTRAINT fn_user FOREIGN KEY (user_id) 
 					REFERENCES sagirdler (user_id),
					fenn VARCHAR(100) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		$table3 = "CREATE TABLE qiymets (
					qiymet_id INT AUTO_INCREMENT PRIMARY KEY,
					user_id INT,
					fenn_id INT,
					KEY qy_fenn_idx (fenn_id),
					KEY qy_user_idx (user_id),
					CONSTRAINT qy_fenn FOREIGN KEY (fenn_id) 
 					REFERENCES fenns (fenn_id),
 					CONSTRAINT qy_user FOREIGN KEY (user_id) 
 					REFERENCES sagirdler (user_id),
 					qiymet INT,
					create_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

		$conn = new mysqli($this->hostname, $this->username, $this->password);
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		}

		$yoxla = $conn->select_db ($this->db); 

		if ($yoxla !== true) {
			$conn->query("CREATE DATABASE ".$this->db);
		}
		$conn->close();

		$conn2 = new mysqli($this->hostname, $this->username, $this->password, $this->db);

		$net = $conn2->query("SHOW TABLES LIKE sagirdler");

		if($net == false){
			$conn2->query($table1);
			$conn2->query($table2);
			$conn2->query($table3);
		}
		self::$conn = $conn2;
	}

	function __sleep()
	{
		$this->conn->close();

		return array('hostname', 'username', 'password','db');
	}

	function __wakeup($array)
	{
		$this->conn = new mysqli($array);
	}

	function __destruct()
	{
		self::$conn->close();
	}

	static public function last_id($sql)
	{
		if (self::$conn->query($sql) === TRUE) {
		  	$last_id = self::$conn->insert_id;
		} else {
		    die(self::$conn->error);
		}
		
		return $last_id;
	}

	static public function yarat($sql)
	{

		if (self::$conn->query($sql) === TRUE) {
		  
		} else {
		    die(self::$conn->error);
		}
		
	}
	static public function all($sql)
	{

		if (self::$conn->connect_error) {
		    die("Connection failed: " . self::$conn->connect_error);
		} 
		$netice=[];
		$result = self::$conn->query($sql);
		
		for($i=0; $i < $result->num_rows; $i++){
			$netice[$i] = $result->fetch_assoc();
		}
		
		return $netice;	
	}
	static public function yoxla($sql)
	{
		if (self::$conn->connect_error) {
		    die("Connection failed: " . self::$conn->connect_error);
		} 
		
		$result = self::$conn->query($sql);
		
		return $result;
	}
	static public function server()
	{
		$mysqli = new mysqli($this->hostname, $this->username, $this->password, $this->db);
		if ($mysqli->connect_error) {
		    die("Connection failed: " . $mysqli->connect_error);
		}
		
		return $mysqli;
	}
	
}


class db extends YeniSQL
{
	function __construct()
	{
		parent::__construct();
	}
	function __destruct()
	{
		parent::__destruct();
	}
	static public function addFenn($data)
	{
		$sql = "INSERT INTO sagirdler (name) VALUES ('".$data[0]."')";

		$last_id = parent::last_id($sql);

		$sql2 = "INSERT INTO fenns (user_id, fenn) VALUES ('";
		for($i = 1; $i < count($data); $i++){
			if($i == count($data)-1){
				$sql2 .= $last_id."', '".$data[$i]."')";
			}
			else{
				$sql2 .= $last_id."', '".$data[$i]."'),('";
			}
		}
		parent::yarat($sql2);
	}

	static public function sagird()
	{
		$sql = "SELECT * FROM sagirdler";
		return parent::all($sql);
	}

	static public function fenn($id)
	{
		$sql = "SELECT * FROM fenns WHERE user_id = ".$id;
		return parent::all($sql);
	}
	static public function yoxlama($name)
	{
		$sql = "IF EXISTS (SELECT * FROM sagirdler WHERE name = ".$name.");";
		$netice = parent::yoxla($sql);

		if ($netice == false){
			return true;
		}
		else{
			return false;
		}
	}
	static public function tarix_yoxlama($user_id, $fenn_id)
	{
		$sql = "IF EXISTS (SELECT * FROM qiymets WHERE user_id = '".$user_id."' AND fenn_id = '".$fenn_id."'";
		
		$netice = parent::yoxla($sql);

		if ($netice == false){
			return true;
		}
		else{
			return false;
		}
	}
	
	static public function edit($request)
	{	

		$sql = "SELECT * FROM qiymets WHERE fenn_id = '".$request['fenn']."' AND create_date BETWEEN '".$request['tarix1']."' AND '".$request['tarix2']."';";
		
		$netice = parent::yoxla($sql);
		
		if($netice->num_rows == 0){
			self::qiymet($request);
		}
		return parent::all($sql);
	}

	static public function update($data)
	{

		foreach ($data as $key => $value) {
			if($key!="_method"){
				if($value != ""){
					parent::yarat("UPDATE qiymets SET qiymet ='".$value."' WHERE qiymet_id = '".$key."'");
				}
			}
		}
		
	}
	static public function qiymet($request)
	{

		$a = date($request['tarix1']);

		$b = date($request['tarix2']);
		$date1=date_create($a);
		$date2=date_create($b);
		$diff=date_diff($date1,$date2);
		$ferq = $diff->format("%a");
		
		if ($ferq > 60) {
			$error = "Intervalin muddeti 60 gunden cox ola bilmez";
			header("Location: qiymet.php? errName=".$error);
			exit();
		}
		for ($i=1; $i < $ferq; $i++) { 
			$date = date("Y-m-d H:i:s", strtotime("+".$i." day", strtotime($a)));
			$sql = "INSERT INTO qiymets (user_id,fenn_id, create_date) VALUES ('".$request['user_id']."','".$request['fenn']."', '".$date."')";
			parent::yarat($sql);
		}
	}
	static public function jurnal($request)
	{
		
		$sg ="";
		$fn ="";
		$a = date($request['tarix1']);

		$b = date($request['tarix2']);
		$date1=date_create($a);
		$date2=date_create($b);
		$diff=date_diff($date1,$date2);
		$ferq = $diff->format("%a");
		if ($ferq > 60) {
			$error = "Intervalin muddeti 60 gunden cox ola bilmez";
			header("Location: four.php?errName=".$error);
			exit();
		}
		if($a == $b or date(strtotime($b)-strtotime($a))/86400 == 1 or date(strtotime($b)-strtotime($a))/86400 == -1){
			$error = "Tarix 1 Tarix2-y bərabər ola bilməz";
			header("Location: four.php?errName=".$error);
			exit();
		}
	
		if(empty($request['sagirdler'])){
			$sg = "";
		}
		if (empty($request['fenns'])) {
			$fn= "";
		}
		if(empty($request['tarix1'])){
			$error = "Tarix1 bos ola bilmez";
			header("Location: four.php? errName=".$error);
			exit();
		}
		if (empty($request['tarix2'])) {
			$error = "Tarix2 bos ola bilmez";
			header("Location: four.php? errName=".$error);
			exit();
		}

		$tr = "qiymets.create_date BETWEEN '".$request['tarix1']."' AND '".$request['tarix2']."'";
		
		
		if(isset($request['sagirdler']) && isset($request['fenns'])){

			for($i=0; $i<count($request['sagirdler']);$i++){
				if ($i==0) {
					$sg.= "fenns.user_id = '".$request['sagirdler'][$i]."'";
				}
				else{
					$sg.= " OR fenns.user_id ='".$request['sagirdler'][$i]."'";
				}
			}

			for ($b=0; $b < count($request['fenns']); $b++) { 
				$fn_id = explode(",", $request['fenns'][$b]);
				if ($b==0) {
					for ($u=0; $u < count($fn_id); $u++) { 
						if ($u==0) {
							$fn.= "fenns.fenn_id = '".$fn_id[$u]."'";
						}
						else{
							$fn.= " OR fenns.fenn_id = '".$fn_id[$u]."'";
						}
					}
				}
				else{
					for ($u=0; $u < count($fn_id); $u++) { 
						
						$fn.= " OR fenns.fenn_id = '".$fn_id[$u]."'";
						
					}
				}
			}
		}
		elseif(isset($request['sagirdler']) && empty($request['fenns'])){

			for($i=0; $i<count($request['sagirdler']);$i++){
				if ($i==0) {
					$sg.= "fenns.user_id = '".$request['sagirdler'][$i]."'";
				}
				else{
					$sg.= " OR fenns.user_id ='".$request['sagirdler'][$i]."'";
				}
			}
			$fn.= "";
		}
		elseif(empty($request['sagirdler']) && isset($request['sagirdler'])){

			$sg .= "";

			for ($b=0; $b < count($request['fenns']); $b++) { 
				$fn_id = explode(",", $request['fenns'][$b]);
				if ($b==0) {
					for ($u=0; $u < count($fn_id); $u++) { 
						if ($u==0) {
							$fn.= "fenns.fenn_id = '".$fn_id[$u]."'";
						}
						else{
							$fn.= " OR fenns.fenn_id = '".$fn_id[$u]."'";
						}
					}
				}
				else{
					for ($u=0; $u < count($fn_id); $u++) { 
						
						$fn.= " OR fenns.fenn_id = '".$fn_id[$u]."'";
						
					}
				}
			}
		}
		
		if($sg != "" && $fn != ""){
			$sql = "SELECT sagirdler.name, fenns.fenn, qiymets.qiymet, qiymets.create_date
			FROM sagirdler
			INNER JOIN qiymets ON (sagirdler.user_id = qiymets.user_id)
			INNER JOIN fenns ON (qiymets.fenn_id = fenns.fenn_id)
			WHERE (".$sg.") AND (".$fn.") AND (".$tr.") ORDER BY sagirdler.name ASC, qiymets.create_date ASC, fenns.fenn";
		}elseif($sg == "" && $fn != ""){
			$sql = "SELECT sagirdler.name, fenns.fenn, qiymets.qiymet, qiymets.create_date
			FROM sagirdler
			INNER JOIN qiymets ON (sagirdler.user_id = qiymets.user_id)
			INNER JOIN fenns ON (qiymets.fenn_id = fenns.fenn_id)
			WHERE (".$fn.") AND (".$tr.") ORDER BY sagirdler.name ASC, qiymets.create_date ASC, fenns.fenn";
		}elseif($sg != "" && $fn == ""){
			$sql = "SELECT sagirdler.name, fenns.fenn, qiymets.qiymet, qiymets.create_date
			FROM sagirdler
			INNER JOIN qiymets ON (sagirdler.user_id = qiymets.user_id)
			INNER JOIN fenns ON (qiymets.fenn_id = fenns.fenn_id)
			WHERE (".$sg.") AND (".$tr.") ORDER BY sagirdler.name ASC, qiymets.create_date ASC, fenns.fenn";
		}else{
			$sql = "SELECT sagirdler.name, fenns.fenn, qiymets.qiymet, qiymets.create_date
			FROM sagirdler
			INNER JOIN qiymets ON (sagirdler.user_id = qiymets.user_id)
			INNER JOIN fenns ON (qiymets.fenn_id = fenns.fenn_id)
			WHERE ".$tr. " ORDER BY qiymets.create_date ASC, sagirdler.name ASC, fenns.fenn";
		}
		

		$data = parent::all($sql);

		return $data;

	} 

	static public function ajax($data)
	{	

		if (is_array($data)) {
			$sql = "SELECT fenn, fenn_id FROM fenns WHERE user_id IN(";
			for ($i=0; $i < count($data); $i++) { 
				if($i == count($data) - 1){
					$sql .= "'".$data[$i]."')"; 
				}
				else{
					$sql .="'".$data[$i]."',";
				}
			}
		}else{
			$sql = "SELECT fenn, fenn_id FROM fenns WHERE user_id = '".$data."'";
		}

		$data = parent::all($sql);
		
		$fenn = array_column($data, "fenn");

		$fenn_id = array_column($data, "fenn_id");

		$fenns = self::array_birlesdir($fenn, $fenn_id);

		echo json_encode($fenns);
		
	}
	static public function array_birlesdir($keys, $values)
	{
	        $result = array();

		    foreach ($keys as $i => $k) {
		     $result[$k][] = $values[$i];
		     }

		    array_walk($result, function(&$v){
		     $v = (count($v) == 1) ? array_pop($v): $v;
		     });

		    return $result;
	}

}




?>

