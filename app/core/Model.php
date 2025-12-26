<?php
class Model {

    protected $conn;
    protected $table;

    public function __construct($db) {
        $this->conn = $db;
    }

    protected function executeQuery($query, $params = []) {
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }
}
