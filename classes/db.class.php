<?php

//classes/db.class.php

class DataBase {
	protected $host;
	protected $database;
	protected $login;
	protected $password;
	protected $req;
	protected $bdd;

	private static $Instance; 

	public function __construct() {
		$this->host = DB_HOST;
		$this->database = DB_NAME;
		$this->login = DB_USER;
		$this->password = DB_PASSWORD;
		$this->bdd = null;
		$this->connect();
	}

	public static function getInstance() 
    { 
        if (!self::$Instance) 
        { 
            self::$Instance = new DataBase(); 
        } 

        return self::$Instance; 
    }

	public function connect() {
		if ($this->bdd == null) {
			try {
				$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
				$pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
				$this->bdd = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->login, $this->password, $pdo_options);

			} catch(Exception $e) {
				die('Erreur de connexion : ' . $e->getMessage());
			}
		}

		return $this->bdd;
	}

	public function beginTransaction(){
		return $this->bdd->beginTransaction();
	}
	public function commit(){
		return $this->bdd->commit();
	}
	public function rollBack(){
		return $this->bdd->rollBack();
	}
	public function exec($sql){
		return $this->bdd->exec($sql);
	}

	public function getLastInsert() {
		return $this->bdd->lastInsertId();
	}

	public function prepare($query) {
		$this->req = null;
		$bdd = $this->connect();
		$this->req = $bdd->prepare($query);
		return $this->req;
	}

	public function execute($anArray) {
		if ($this->req != null) {
			$this->req->execute($anArray);
		}
	}

	public function fetch($param=PDO::FETCH_ASSOC) {
		if ($this->req != null) {
			$this->req->fetch($param);
		}
	}
}