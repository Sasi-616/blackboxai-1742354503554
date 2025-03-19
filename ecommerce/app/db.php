<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $statement;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->logError('Connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    // Singleton pattern to ensure only one database connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Prepare and execute a query
    public function query($sql, $params = []) {
        try {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($params);
            return $this;
        } catch (PDOException $e) {
            $this->logError('Query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed');
        }
    }

    // Fetch a single row
    public function fetch() {
        return $this->statement->fetch();
    }

    // Fetch all rows
    public function fetchAll() {
        return $this->statement->fetchAll();
    }

    // Fetch a single column
    public function fetchColumn() {
        return $this->statement->fetchColumn();
    }

    // Get the number of affected rows
    public function rowCount() {
        return $this->statement->rowCount();
    }

    // Get the last inserted ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    // Begin a transaction
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    // Commit a transaction
    public function commit() {
        return $this->connection->commit();
    }

    // Rollback a transaction
    public function rollBack() {
        return $this->connection->rollBack();
    }

    // Quote a string for use in a query
    public function quote($string) {
        return $this->connection->quote($string);
    }

    // Log database errors
    private function logError($message) {
        $logFile = LOG_DIR . 'database.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        
        error_log($logMessage, 3, $logFile);
    }

    // Example helper methods for common queries
    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";

            $this->query($sql, $values);
            return $this->lastInsertId();
        } catch (Exception $e) {
            $this->logError('Insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update($table, $data, $where, $whereParams = []) {
        try {
            $fields = array_keys($data);
            $set = array_map(function($field) {
                return "$field = ?";
            }, $fields);

            $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
            
            $params = array_merge(array_values($data), $whereParams);
            $this->query($sql, $params);
            return $this->rowCount();
        } catch (Exception $e) {
            $this->logError('Update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            $this->query($sql, $params);
            return $this->rowCount();
        } catch (Exception $e) {
            $this->logError('Delete failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        try {
            $sql = "SELECT $columns FROM $table";
            
            if ($where) {
                $sql .= " WHERE $where";
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }
            
            if ($limit) {
                $sql .= " LIMIT $limit";
            }

            $this->query($sql, $params);
            return $this;
        } catch (Exception $e) {
            $this->logError('Select failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Example usage:
/*
try {
    $db = Database::getInstance();
    
    // Insert example
    $userId = $db->insert('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);

    // Select example
    $users = $db->select('users', '*', 'age > ?', [18], 'name ASC', '10')
                ->fetchAll();

    // Update example
    $affected = $db->update('users', 
        ['status' => 'active'],
        'id = ?',
        [1]
    );

    // Delete example
    $deleted = $db->delete('users', 'id = ?', [1]);

    // Transaction example
    $db->beginTransaction();
    try {
        $db->insert('orders', ['user_id' => 1, 'total' => 99.99]);
        $db->update('inventory', ['quantity' => 'quantity - 1'], 'product_id = ?', [123]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Handle error
    error_log($e->getMessage());
}
*/
