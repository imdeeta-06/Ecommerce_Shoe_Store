<?php

namespace App\Models;

use PDO;

class BaseModel {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($table) {
        $stmt = $this->db->prepare("SELECT * FROM {$table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($table, $id) {
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($table, $id) {
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = $this->db->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    public function update($table, $id, $data) {
        $setClause = '';
        foreach (array_keys($data) as $key) {
            $setClause .= "{$key} = ?, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->db->prepare("UPDATE {$table} SET {$setClause} WHERE id = ?");
        return $stmt->execute($values);
    }

    public function softDelete($table, $id) {
        $stmt = $this->db->prepare("UPDATE {$table} SET status = 0 WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
