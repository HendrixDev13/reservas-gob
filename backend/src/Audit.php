<?php
declare(strict_types=1);
namespace App;

final class Audit {
    public static function record(?int $userId, string $action, string $entity, ?int $entityId = null, array $details = []): void {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO auditoria (usuario_id,accion,entidad,entidad_id,detalle,ip,user_agent) VALUES (?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $userId, $action, $entity, $entityId,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);
        } catch (\Throwable $e) {
            Logger::write('error', 'No se pudo guardar auditoría', ['error'=>$e->getMessage(),'entity'=>$entity,'action'=>$action]);
        }
    }
}
