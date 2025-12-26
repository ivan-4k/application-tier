<?php
namespace App\Core;

use App\Config\Database;

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function findAll($conditions = [], $orderBy = '', $limit = '') {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClauses = [];
            $params = [];
            
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
                $params[] = $value;
            }
            
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    public function findOne($conditions) {
        $result = $this->findAll($conditions, '', 1);
        return !empty($result) ? $result[0] : null;
    }
    
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        
        return false;
    }
    
    public function update($conditions, $data) {
        $setClauses = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $params[] = $value;
        }
        
        $whereClauses = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . 
               " WHERE " . implode(' AND ', $whereClauses);
        
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        
        return $stmt->execute();
    }
    
    public function delete($conditions) {
        $whereClauses = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = ?";
            $params[] = $value;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $whereClauses);
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        
        return $stmt->execute();
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>