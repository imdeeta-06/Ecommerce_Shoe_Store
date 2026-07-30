<?php

namespace App\Models;

use PDO;

class System extends BaseModel {
    public function __construct() {
        parent::__construct();
        // Xử lý logic cho các bảng: logs, setting, custom_notes
    }

    // --- SETTING ---
    public function createSetting($data) { return $this->insert('setting', $data); }
    public function getSetting($keyName) {
        $stmt = $this->db->prepare("SELECT value FROM setting WHERE key_name = :key_name");
        $stmt->execute(['key_name' => $keyName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : null;
    }
    public function getSettingById($id) { return $this->getById('setting', $id); }
    public function updateSetting($id, $data) { return $this->update('setting', $id, $data); }
    public function deleteSetting($id) { return $this->delete('setting', $id); }

    // --- LOGS ---
    public function createLog($data) { return $this->insert('logs', $data); }
    public function getLog($id) { return $this->getById('logs', $id); }
    public function getLogsByUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM logs WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CUSTOM_NOTES ---
    public function createCustomNote($data) { return $this->insert('custom_notes', $data); }
    public function getCustomNote($id) { return $this->getById('custom_notes', $id); }
    public function updateCustomNote($id, $data) { return $this->update('custom_notes', $id, $data); }
    public function getNotesByEntity($entityType, $entityId) {
        $stmt = $this->db->prepare("SELECT * FROM custom_notes WHERE entity_type = :entity_type AND entity_id = :entity_id");
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
