<?php
/**
 * Database Class - Singleton PDO Wrapper
 * 
 * Provides a centralized database connection with helper methods
 * for common database operations.
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }
    
    /**
     * Execute a query and return all results
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array Array of results
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute a query and return single row
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array|false Single row or false
     */
    public function fetchOne(string $sql, array $params = []): array|false {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Execute a query and return single value
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return mixed Single value
     */
    public function fetchValue(string $sql, array $params = []): mixed {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Execute an INSERT, UPDATE, or DELETE query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Insert a row and return the last inserted ID
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last inserted ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Update rows in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }
        $setString = implode(', ', $setClauses);
        
        $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete rows from a table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }
    
    /**
     * Check if a value exists in a table
     * 
     * @param string $table Table name
     * @param string $column Column to check
     * @param mixed $value Value to look for
     * @param int|null $excludeId ID to exclude (for updates)
     * @return bool
     */
    public function exists(string $table, string $column, mixed $value, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return (int) $this->fetchValue($sql, $params) > 0;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
