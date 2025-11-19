<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bitsa_club');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');


// PDO Connection (for Auth class)
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER, 
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// MySQLi Connection (for Models)
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    public $conn;
    public $error;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->conn->connect_error) {
            $this->error = "Connection failed: " . $this->conn->connect_error;
            error_log("Database connection error: " . $this->conn->connect_error);
            return false;
        }
        
        $this->conn->set_charset(DB_CHARSET);
        return true;
    }

    public function query($query) {
        $result = $this->conn->query($query);
        if (!$result) {
            $this->error = $this->conn->error;
            error_log("Query error: " . $this->conn->error);
        }
        return $result;
    }

    public function prepare($query) {
        return $this->conn->prepare($query);
    }

    public function lastInsertId() {
        return $this->conn->insert_id;
    }
}
?>