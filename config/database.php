<?php
// config/database.php - Database Connection

class Database {
    private $host = 'localhost';
    private $db_name = 'ecommerce_toys';
    private $user = 'root';
    private $password = '';
    private $pdo;
    
    public function connect() {
        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8';
            $this->pdo = new PDO($dsn, $this->user, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->pdo;
        } catch(PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

$db = new Database();
$pdo = $db->connect();
?>