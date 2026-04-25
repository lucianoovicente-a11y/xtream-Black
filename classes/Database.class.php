<?php
/**
 * Classe Database - Gerenciador de conexão e operações com banco de dados
 */

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado para implementar Singleton
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Obter instância única da classe
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Estabelecer conexão com o banco de dados
     */
    private function connect() {
        try {
            $db_config = conectar_bd();
            if ($db_config) {
                $this->connection = $db_config;
            } else {
                throw new Exception("Falha ao conectar ao banco de dados");
            }
        } catch (PDOException $e) {
            system_log('Erro de conexão PDO: ' . $e->getMessage(), LOG_LEVEL_ERROR);
            throw new Exception("Erro de conexão com o banco de dados");
        }
    }
    
    /**
     * Obter conexão PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Executar query simples
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            system_log('Erro na query: ' . $e->getMessage() . ' | SQL: ' . $sql, LOG_LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Buscar um único registro
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }
    
    /**
     * Buscar todos os registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    /**
     * Inserir registro
     */
    public function insert($table, $data) {
        try {
            $keys = array_keys($data);
            $fields = implode(', ', $keys);
            $placeholders = ':' . implode(', :', $keys);
            
            $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            $stmt->execute();
            return $this->connection->lastInsertId();
            
        } catch (PDOException $e) {
            system_log('Erro no insert: ' . $e->getMessage(), LOG_LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Atualizar registro
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $set = [];
            foreach ($data as $key => $value) {
                $set[] = "{$key} = :{$key}";
            }
            $setString = implode(', ', $set);
            
            $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            foreach ($whereParams as $key => $value) {
                $stmt->bindValue(":w_{$key}", $value);
            }
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            system_log('Erro no update: ' . $e->getMessage(), LOG_LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Deletar registro
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            system_log('Erro no delete: ' . $e->getMessage(), LOG_LEVEL_ERROR);
            return false;
        }
    }
    
    /**
     * Contar registros
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchColumn();
        }
        return 0;
    }
    
    /**
     * Executar transação
     */
    public function transaction($callback) {
        try {
            $this->connection->beginTransaction();
            $result = $callback($this);
            $this->connection->commit();
            return $result;
        } catch (Exception $e) {
            $this->connection->rollBack();
            system_log('Erro na transação: ' . $e->getMessage(), LOG_LEVEL_ERROR);
            throw $e;
        }
    }
    
    /**
     * Começar transação
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit da transação
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback da transação
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Verificar se tabela existe
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->query($sql, [$table]);
        return $stmt && $stmt->rowCount() > 0;
    }
    
    /**
     * Obter informações da tabela
     */
    public function getTableInfo($table) {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }
    
    /**
     * Truncar tabela
     */
    public function truncate($table) {
        return $this->query("TRUNCATE TABLE {$table}");
    }
    
    /**
     * Clone e wakeup privados para prevenir clonagem
     */
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
