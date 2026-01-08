<?php
/**
 * Barron Production Management System
 * Database Configuration and Connection
 */

class Database {
    // Railway MySQL Configuration
    private $host = "caboose.proxy.rlwy.net";
    private $port = "20038";
    private $db_name = "railway";
    private $username = "root";
    private $password = "EDDEmqdRstvoHdqCmEflYJrnpaBwWajy";
    
    public $conn;
    
    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            $options = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false
            );
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if ($this->conn) {
            return $this->conn->beginTransaction();
        }
        return false;
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        if ($this->conn) {
            return $this->conn->commit();
        }
        return false;
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->conn && $this->conn->inTransaction()) {
            return $this->conn->rollBack();
        }
        return false;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        if ($this->conn) {
            return $this->conn->lastInsertId();
        }
        return null;
    }
}
