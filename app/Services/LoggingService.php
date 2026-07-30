<?php

namespace App\Services;

use App\Models\Database;
use Exception;

class LoggingService {
    public static function write($actorId, $action, $description, $targetId = null) {
        try {
            $db = Database::getInstance()->getConnection();

            // Ghi log hệ thống, không để lỗi ghi log ảnh hưởng luồng chính.
            $stmt = $db->prepare("
                INSERT INTO logs (actor_id, action, description, target_id, created_at)
                VALUES (:actor_id, :action, :description, :target_id, NOW())
            ");

            return $stmt->execute([
                'actor_id' => $actorId,
                'action' => $action,
                'description' => $description,
                'target_id' => $targetId
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
}
