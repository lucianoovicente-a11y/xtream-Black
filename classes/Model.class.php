<?php
/**
 * Classe Model - Classe base para todos os models
 */

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Buscar todos os registros
     */
    public function all($orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Buscar por ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Buscar com condições
     */
    public function where($conditions, $params = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Criar novo registro
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Atualizar registro
     */
    public function update($id, $data) {
        return $this->db->update(
            $this->table, 
            $data, 
            "{$this->primaryKey} = :id", 
            ['id' => $id]
        );
    }
    
    /**
     * Deletar registro
     */
    public function delete($id) {
        return $this->db->delete(
            $this->table, 
            "{$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    /**
     * Contar registros
     */
    public function count($where = '', $params = []) {
        return $this->db->count($this->table, $where, $params);
    }
    
    /**
     * Buscar primeiro registro
     */
    public function first($conditions = null, $params = []) {
        $sql = "SELECT * FROM {$this->table}";
        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        $sql .= " LIMIT 1";
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * Paginação
     */
    public function paginate($page = 1, $perPage = 20, $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Total de páginas
     */
    public function totalPages($perPage = 20) {
        $total = $this->count();
        return ceil($total / $perPage);
    }
}
