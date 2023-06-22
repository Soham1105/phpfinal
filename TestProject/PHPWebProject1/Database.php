<?php
class Database{
	private PDO $connection;
	private static ?Database $instance = null;
	public function __construct(){
		try{
            $this->connection = new PDO("mysql:host=127.0.0.1;port=3306;dbname=FacultyFeedback", 'root', '123456789');
			$this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e){
			die($e->getMessage());
		}
	}
	public static function getInstance() :self {
		if(!self::$instance) self::$instance = new Database();
		return self::$instance;
	}
	public function getConnection() :PDO { return $this->connection; }
	public function prepare(string $sql):PDOStatement{
		return $this->connection->prepare($sql);
	}
	public function execute(PDOStatement $stmt,array $params){
        $stmt->execute($params);
    }
	public function getData(string $sql) : array|null{
		$stmt = $this->connection->prepare($sql);
		$stmt->execute();
		return $stmt->fetchALL(PDO::FETCH_ASSOC);
	}
	public function insert(PDOStatement $stmt,array $values){
		try{
			array_map(
				function($key, $value) use ($stmt){
					$stmt->bindValue(':' . $key,$value);
				},array_keys($values),array_values($values)
			);
			$stmt->execute();
		}catch(PDOException $e){
			die($e->getMessage());
		}
	}
	public function __destruct(){ $instance = $connection = null; }
}