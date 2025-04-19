<?php
// Create mysqli connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "social"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Database class for PDO connection (used by login.php)
class Database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "social";
    private $conn = null;
    
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", 
                                   $this->username, 
                                   $this->password);
                
                // Set the PDO error mode to exception
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
            } catch(PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        
        return $this->conn;
    }
}
?>