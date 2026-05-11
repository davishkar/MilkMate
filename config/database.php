<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'milkmate_db');

class Database {
    private static $instance = null;
    private $pdo;
    private function __construct() {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $opts = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false];
        try { $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $opts); }
        catch(PDOException $e) { die(json_encode(['success'=>false,'message'=>'DB Error: '.$e->getMessage()])); }
    }
    public static function getInstance() { if(!self::$instance) self::$instance=new self(); return self::$instance; }
    public function getConnection() { return $this->pdo; }
}
function db() { return Database::getInstance()->getConnection(); }
?>
