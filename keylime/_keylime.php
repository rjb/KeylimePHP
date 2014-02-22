<?php
require_once ("DB.php");
require_once ("_config.php");

function nav_login_join($user) {
	if (!empty($user)) {
		$userOrLoggedIn = "Logged in as $user";
		$userOrLoggedIn .= ' <small>|</small> <a href="/logout" class="">logout</a>';
	} else {
		$userOrLoggedIn = '<a href="/login">Login</a>';
		$userOrLoggedIn .= ' <small>or</small> <a href="/signup" class="">Sign Up</a>';
	}

	return $userOrLoggedIn;
}

class DBConfig {	
	static public function dbVars() {
		$DATABASE = DB_DATABASE;
		$USER = DB_USER;
		$PASSWORD = DB_PASSWORD;
		$SERVER = DB_SERVER;
		
		return "mysql://$USER:$PASSWORD@$SERVER/$DATABASE";
	}
}

class Database {
	// Database Connection Handle
	private $dbconn;
	
	private function __construct() {
		// Get db configuration and then connect
		$dsn = DBConfig::dbVars();
		$this->dbconn =& DB::Connect($dsn, array());
		
		if (PEAR::isError($this->dbconn)) {
			throw new Exception('Unable to connect to the database');
		}
	}
	
	// Creates and returns singleton
	static public function instance() {
		static $objDB;
		
		if(!isset($objDB)) {
			$objDB = new Database();
		}
		
		return $objDB;
	}
	
	public function startTransaction() {
		// true/false returned if commands succeed
		return $this->dbconn->autoCommit(false);
	}
	
	public function commit() {
		$result = $this->dbconn->commit();
		
		if(DB::isError($result)) {
			throw new Exception($result->getMessage());
		}
		
		$this->dbconn->autoCommit(true);
		return true;
	}
	
	public function abort() {
		$result = $this->dbconn->rollback();
		
		if(DB::isError($result)) {
			throw new Exception($result->getMessage());
		}
		
		return true;
	}
	
	public function select($sql) {
		$result = $this->dbconn->query($sql);
		
		if (DB::isError($result)) {
			throw new Exception($result->getMEssage());
		}
		
		$items = array();
		while ($result->fetchInto($row, DB_FETCH_ASSOC)) {
			$items[] = $row;
		}
		
		return $items;
	}
	
	public function single($tableName, Array $keyAndValue) {
		/* 
		Find an item by id
		Example: $obj->single("businesses", array( 'business_id'=>'6' ));
		A way around having to enter the field would be to standardize id field
		to either pluralized or simply `id`. So `businesses_id` or `id`.
		Or create a method to pull out the field name when given a table name???
		*/
		
		// Escape the table
		//tableName = $this->dbconn->quoteIdentifier($table);

		$key = implode(", ", array_keys($keyAndValue));
		$value = array_values($keyAndValue);
		
		$result = $this->dbconn->query("SELECT * FROM $tableName WHERE $key = ?", $value);
		$result->fetchInto($row);
		if (!$row) {
			throw new Exception('Unable to find item by id');
		}
		
		return $row;
	}
	
	public function insert($table, Array $fieldsAndValues) {
		/* 
		Takes a table name and an associative array.
		Example: $obj->insert("businesses", array( 'business_name'=>'Wagamama' ));
		*/
		
		// Escape the table
		$table = $this->dbconn->quoteIdentifier($table);
		
		// Exracts the fieldsValues array. Since Fields are used the orrder does not matter.
		$fields = implode(", ", array_keys($fieldsAndValues));
		$values = array_values($fieldsAndValues);
		
		// This counts the number of values to be passed
		// so as to create the number of '?' marks
		$qMark = array();
		for ($i=0;$i<count($values);$i++) {
			$qMark[] = "?";
		}
		$qMark = implode(", ", $qMark);

		$prepare = $this->dbconn->prepare("INSERT INTO $table ($fields) VALUES ($qMark)");
		$result = $this->dbconn->execute($prepare, $values);
		
		if(DB::isError($result)) {
			throw new Exception("Error inserting item into database");
		}
		
		return array($this->dbconn->affectedRows(), mysql_insert_id());
	}
	
	public function update($table, $conditions, Array $fieldsAndValues) {
		// This should support updating a database object
		// So if you run a select then you should be able to pass that result in to the update function
		// Maybe create a new one... and maybe change the name of this one... need to check all code or course
		
		// quoteSmart the fieldsAndValues Array
		$fv = array();
		foreach($fieldsAndValues as $field => $value) {
			$fv[] = $field . ' = ' . $this->dbconn->quoteSmart($value);
		}
		$fvSet = implode(", ", $fv);		
		
		// Escape the table
		$table = $this->dbconn->quoteIdentifier($table);

		$prepare = $this->dbconn->prepare("UPDATE $table SET ? ?)");
		$result = $this->dbconn->execute($prepare, array($fvSet, $conditions));

		$sql = "UPDATE $table SET $fvSet $conditions";
		$result = $this->dbconn->query($sql);
		
		if(DB::isError($result)) {
			throw new Exception($result->getMessage());
		}
	}
	
	public static function _getConnection() {
		$DATABASE = DB_DATABASE;
		$USER = DB_USER;
		$PASSWORD = DB_PASSWORD;
		$SERVER = DB_SERVER;
		
		$dsn = "mysql://$USER:$PASSWORD@$SERVER/$DATABASE";
		$db =& DB::Connect($dsn, array());
		if (PEAR::isError($db)) {
			throw new Exception('Unable to connect to the database');
		}
		return $db;
	}
	
	public function __destruct() {
		$this->dbconn->disconnect();
	}
}

class UserSession {
	private $php_session_id;
	private $native_session_id;
	private $dbh;
	private $logged_in;
	private $user_id;
	private $session_timeout = 600;
	// private $session_lifespan = 36000;
	
	public function __construct($session_lifespan = 36000) {
		$this->dbh = Database::instance();

		session_set_save_handler(
			array(&$this, '_session_open_method'),
			array(&$this, '_session_close_method'),
			array(&$this, '_session_read_method'),
			array(&$this, '_session_write_method'),
			array(&$this, '_session_destroy_method'),
			array(&$this, '_session_gc_method')
		);
		
		$strUserAgent = $GLOBALS['HTTP_USER_AGENT'];
		if ($_COOKIE['session']) {
			$this->php_session_id = $_COOKIE['session'];
			$stmt = $this->dbh->select("SELECT * FROM user_sessions
								 		WHERE ascii_session_id = \"{$this->php_session_id}\"
								 		AND ((now() - created) < \"$session_lifespan\")
										AND ((now() - last_impression) <= \"{$this->session_timeout}\")
										OR last_impression = NULL");
		}
		session_set_cookie_params($session_lifespan, "/", ".slimphp.dev");
		session_start();
	}
	
	public function impress() {
		if ($this->native_session_id) {
			$result = $this->dbh->update("user_sessions", 
										 "WHERE user_session_id = \"{$this->native_session_id}\"", 
										 array('last_impression'=>date('Y-m-d G:i:s')));
		}
	}
	
	public function isLoggedIn() {
		return $this->logged_in;
	}
	
	public function getUserId() {
		if ($this->logged_in) {
			return $this->user_id;
		} else {
			return false;
		}
	}
	
	public function getUserObject() {
		if ($this->logged_in) {
			$objUser = new User($this->user_id);
			return $objUser;
		} else {
			return false;
		}
	}
	
	public function getSessionIdentifier() {
		return $this->php_session_id;
	}
	
	public function login($username, $plainPassword) {
		$md5Password = md5($plainPassword);
		$stmt = $this->dbh->select("SELECT person_id 
									FROM people
									WHERE person_screenname = \"$username\"
									AND person_md5_password = \"{$md5Password}\"");
		
		if (!empty($stmt)) {
			$this->user_id = $stmt[0][0];
			$nativeSessionId = $this->native_session_id;
			$this->logged_in = true;
			$result = $this->dbh->update("user_sessions", 
										 "WHERE user_session_id = $nativeSessionId",
										  array('logged_in'=>1));
										
 			$result = $this->dbh->update("user_sessions", 
 										 "WHERE user_session_id = $nativeSessionId",
 										  array('person_id'=>$this->user_id));
			return true;
		} else {
			return false;
		}
	}
	
	public function logout() {
		if ($this->logged_in == true) {
			$nativeSessionId = $this->native_session_id;
			$result = $this->dbh->update("user_sessions", 
										 "WHERE user_session_id = \"{$nativeSessionId}\"",
										  array('logged_in'=>0));
										
		    $result = $this->dbh->update("user_sessions", 
		   							 	 "WHERE user_session_id = \"{$nativeSessionId}\"",
		   							  	  array('person_id'=>0));
			$this->logged_in = false;
			$this->user_id = 0;
			return true;
		} else {
			return false;
		}
	}
	
	public function __get($nm) {
		$sessionId = $this->native_session_id;
		$result = $this->dbh->select("SELECT *
									  FROM session_vars
									  WHERE session_id = \"{$sessionId}\"
									  AND session_var_name = \"{$nm}\"");
      	if ($result) {
        	return(unserialize($result[0][2]));
		} else {
        	return(false);
      	};
    }
   	
    public function __set($nm, $val) {
		$valSer = serialize($val);
		$name = $nm;
		$sessionId = $this->native_session_id;
		$result = $this->dbh->select("SELECT *
									  FROM session_vars
									  WHERE session_id = \"{$sessionId}\"
									  AND session_var_name = \"{$name}\"");

		if (empty($result)) {
			$result = $this->dbh->insert("session_vars", array('session_var_name'=>$name,
															   'session_var_value'=>$valSer,
															   'session_id'=>$this->native_session_id));
		} else {
			// Update is really in place for AUTH variable
			// which checks form validation via random md5 key
			$result = $this->dbh->update("session_vars", "WHERE session_id = \"{$sessionId}\"
										  				  AND session_var_name = \"{$name}\"",
		   							  	  						array('session_var_value'=>$valSer));
		}
    }
	
	private function _session_open_method($savePath, $sessionName) {
		// nada
		return true;
	}
	
	public function _session_close_method() {
		// DB Closes on destruct so do nothing here
		return true;
	}
	
	private function _session_read_method($id) {
		$strUserAgent = $_SERVER['HTTP_USER_AGENT'];
		$this->php_session_id = $id;
		$failed = 1;
		$result = $this->dbh->select("SELECT *
									  FROM user_sessions
							 		  WHERE ascii_session_id = \"{$id}\"");
		if (!empty($result)) {
			$this->native_session_id = $result[0][0];
			if ($result[0][2] == 1) {
				$this->logged_in = true;
				$this->user_id = $result[0][6];
			} else {
				$this->logged_in = false;
			}
		} else {
			$this->logged_in = false;
			$item = $this->dbh->insert("user_sessions", array('ascii_session_id'=>$id,
													   'logged_in'=>0,
													   'person_id'=>0,
													   'created'=>date('Y-m-d G:i:s'),
													   'user_agent'=>$strUserAgent));
		    $result = $this->dbh->select("SELECT user_session_id
		   							  	  FROM user_sessions
		   					 		  	  WHERE ascii_session_id = \"{$id}\"");
			$this->native_sesion_id = $result[0][0];
		}
		return "";
	}
	
	public function _session_write_method($id, $sessData) {
		return true;
	}
	
	private function _session_destroy_method($id) {
		$result = $this->dbh->select("DELETE FROM user_sessions
	   					 		  	  WHERE ascii_session_id \"{$id}\"");
		return true;
	}
	
	private function _session_gc_method($maxLifetime) {
		return true;
	}
}

class FormHandler {
	public $dbh;
	public $error_messages = array();
	
	public function __construct() {
		$this->dbh = Database::instance();
	}
	
	public function checkGenericField($field) {
		$field = $field;
		
		if (empty($field)) {
			$this->error_messages['field'] = Config::$formErrorMessages['field']['empty'];
		}
		
		return true;
	}
	
	public function checkGenericEmail($email) {
		$email = trim($email);
				
		if (empty($email)) {
			$this->error_messages['email'] = Config::$formErrorMessages['field']['empty'];
		} elseif (!preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-zA-Z.]{2,5}/i", $email)) {
		  	$this->error_messages['email'] = Config::$formErrorMessages['email']['incorrect-format'];
		}
		
		return true;
	}
	
	public function checkGenericEmailExists($email) {
		$email = trim($email);
		
		$emailExists = $this->dbh->select("SELECT * FROM people WHERE person_email = '$email'");
				
		if (!$emailExists) {
		  	$this->error_messages['email'] = Config::$formErrorMessages['email']['non-existent'];
		}
		
		return true;
	}
	
	public function getErrorMessages() {
		return $this->error_messages;
	}
	
	public function errors() {
		if (empty($this->error_messages)) {
			// If error_messages array is empty then false... no errors
			return false;
		} else {
			return true;
		}
	}
	
	// Function to check for any non char or int
}

class LoginFormHandler extends FormHandler {
	// Login form
	// Add login attempt count checking - Looks to a table to the number of times within 
	// a 5-10 minute perios they (IP Checking) tried to login
	// If > 15 - Then boot 'em
	
	public function checkScreenName($screenName) {
		$screenName = $screenName;
		
		if (empty($screenName)) {
			$this->error_messages['screenname'] = Config::$formErrorMessages['field']['empty'];
		}
	}
	
	public function checkPassword($pass) {
		$password = $pass;
		
		if (empty($password)) {
			$this->error_messages['password'] = Config::$formErrorMessages['field']['empty'];
		}
	}
}

class JoinFormHandler extends FormHandler {

	public function checkScreenName($screenName) {
		// Trim it
		$screenName = trim($screenName);
		
		// Run select on people database - A user class needs to be created with sceenNameExists() function
		$person = $this->dbh->select("SELECT * FROM people WHERE person_screenname = '$screenName'");
				
		if (empty($screenName)) {
			$this->error_messages['screenname'] = Config::$formErrorMessages['field']['empty'];
		} elseif (!empty($person)) {
			$this->error_messages['screenname'] = Config::$formErrorMessages['screenname']['taken'];
		} elseif (strlen($screenName) < 3) {
			$this->error_messages['screenname'] = Config::$formErrorMessages['screenname']['too-short'];
		} elseif (strlen($screenName) > 12) {
			$this->error_messages['screenname'] = Config::$formErrorMessages['screenname']['too-long'];
		} elseif (preg_match("/[^a-z0-9\-\_]/i", $screenName)) {
			// Just in case something slips by.
			// Non accepted chars are stripped before being sent here
			$this->error_messages['screenname'] = Config::$formErrorMessages['field']['non-char-int'];
		}

		return true;
	}
	
	public function checkPasswords($pass1, $pass2) {
		if (empty($pass1)) {
			$this->error_messages['password1'] = Config::$formErrorMessages['field']['empty'];
		} elseif (strlen($pass1) < 6 ) {
			$this->error_messages['password1'] = Config::$formErrorMessages['password']['too-short'];
		}
		
		if (empty($pass2)) {
			$this->error_messages['password2'] = Config::$formErrorMessages['field']['empty'];
		}
		
		// This checks if passwords are the same only if first pass field is not empty.
		// Since two blank fields will equate to the same value.
		// I.E. the user gets most informative message
		if ($pass1 != $pass2 && !$this->error_messages['password1'] && !$this->error_messages['password2']) {
			$this->error_messages['password1'] = Config::$formErrorMessages['password']['dont-match'];
		}
		
		return true;
	}
	
	public function checkEmail($email) {
		$email = trim($email);
		
		// See if email is being used by anyone else
		$emailExists = $this->dbh->select("SELECT * FROM people WHERE person_email = '$email'");
		
		if (empty($email)) {
			$this->error_messages['email'] = Config::$formErrorMessages['field']['empty'];
		} elseif (!preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-zA-Z.]{2,5}/i", $email)) {
		  	$this->error_messages['email'] = Config::$formErrorMessages['email']['incorrect-format'];
		} elseif ($emailExists) {
		  	$this->error_messages['email'] = Config::$formErrorMessages['email']['taken'];
		}
		
		return true;
	}
}

class Uri {
	private $domain;
	private $subdomain;
	
	public function __construct() {
		$host = explode(".", $_SERVER['HTTP_HOST'], 2);
		$this->domain = $host[1];
		$this->subdomain = $host[0];
		
		$uri = $_SERVER['REQUEST_URI'];
		$confirmUri = parse_url($_SERVER['REQUEST_URI']);
		$confirmUri = str_word_count($confirmUri['path'], 1);
		$confirmUri = $confirmUri[0];
	}
	
	public function get_domain() {
		return $this->domain;
	}
	
	public function get_subdomain() {
		return $this->subdomain;
	}
}

class Controller {
	private $session;
	private $db;
	private $loggedInScreenname;
	
	public function __construct() {
		$this->session = new UserSession();
		$this->session->impress();
		$this->db = Database::instance();
				
		if ($this->session->isLoggedIn()) { 
			$personId = $this->session->getUserId();
			$user = $this->db->single("people", array('person_id'=>$personId));
			$this->loggedInScreenname = $user[1];
		}
	}
	
	public function get_screenname() {
		return $this->loggedInScreenname;
	}
	
	public function get_session() {
		return $this->session;
	}
}

class Keylime {
	private $param;
	
	public function __construct() {
		$host = explode(".", $_SERVER['HTTP_HOST'], 2);
		$uri = parse_url($_SERVER['REQUEST_URI']);
		
		// Has to be a better way to get this.
		$uriTemp = explode("/", ($uri['path']));
		
		$uri = str_word_count($uri['path'], 1);
    	
		$sub_domain = $host[0];
		$client_class = $uri[0];
		$client_method = $uri[1];
		$this->param = $uriTemp[2];
	}
	
	public function get_param() {
		return $this->param;
	}
	
	public static function run($urls) {
		// run() handles all delegation of the client's code.
		
		// Parse the URL
		$host = explode(".", $_SERVER['HTTP_HOST'], 2);
		$uri = parse_url($_SERVER['REQUEST_URI']);
		$uri = str_word_count($uri['path'], 1);

		$sub_domain = $host[0];
		$client_class = $uri[0];
		$client_method = $uri[1];
		$client_param = $uri[2];
		
		if (!empty($client_class) && in_array($client_class, array_keys($urls))) {
			$instance = new $urls[$client_class]();
			if (method_exists($instance, "__index__")) {
				$instance->__index__();
			} else {
				print "Page has gone missing";
			} 
		} elseif (empty($client_class)) {
			$instance = new $urls['']();
			$instance->__index__();
		} else {
			print "Page has gone missing";
		}
	}
}
?>