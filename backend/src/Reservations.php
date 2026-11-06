<?php
declare(strict_types=1);
namespace App;

use PDO;

final class Reservations {
    public static function list(PDO $pdo, array $query): array {
        $sql = "SELECT r.*,s.nombre AS salon_nombre,u.nombre AS creado_por, COALESCE((SELECT string_agg(e.nombre, ', ' ORDER BY e.nombre) FROM reserva_equipos re JOIN equipos e ON e.id=re.equipo_id WHERE re.reserva_id=r.id), '') AS equipamiento_nombres FROM reservas r JOIN salones s ON s.id=r.salon_id JOIN usuarios u ON u.id=r.usuario_id WHERE 1=1";
        $args = [];
        if (!empty($query['from'])) { $sql .= ' AND r.fecha_inicio >= ?'; $args[] = $query['from']; }
        if (!empty($query['to'])) { $sql .= ' AND r.fecha_inicio < ?'; $args[] = $query['to']; }
        if (!empty($query['estado'])) { $sql .= ' AND r.estado = ?'; $args[] = $query['estado']; }
        $sql .= ' ORDER BY r.fecha_inicio DESC LIMIT 500';
        $st = $pdo->prepare($sql); $st->execute($args);
        return $st->fetchAll();
    }

    public static function create(PDO $pdo, array $data, array $user): int {
        $salonId = filter_var($data['salon_id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim((string)($data['titulo'] ?? ''));
        $requester = trim((string)($data['solicitante'] ?? ''));
        $start = trim((string)($data['fecha_inicio'] ?? ''));
        $end = trim((string)($data['fecha_fin'] ?? ''));
        if (!$salonId || $title === '' || $requester === '' || $start === '' || $end === '') {
            throw new \InvalidArgumentException('Completa salón, título, solicitante, fecha de inicio y fecha de fin.');
        }
        try { $a = new \DateTimeImmutable($start); $b = new \DateTimeImmutable($end); }
        catch (\Throwable) { throw new \InvalidArgumentException('Formato de fecha inválido.'); }
        if ($b <= $a) throw new \InvalidArgumentException('La fecha de fin debe ser posterior al inicio.');
        if ($a < new \DateTimeImmutable('-5 minutes')) throw new \InvalidArgumentException('No puedes registrar una reserva en el pasado.');
        $pdo->beginTransaction();
        try {
            // Lock del salón para serializar intentos simultáneos de reservar el mismo salón.
            $lock = $pdo->prepare('SELECT id FROM salones WHERE id=? AND activo IS TRUE FOR UPDATE');
            $lock->execute([$salonId]);
            if (!$lock->fetch()) throw new \InvalidArgumentException('El salón no existe o está inactivo.');
            $overlap = $pdo->prepare("SELECT id FROM reservas WHERE salon_id=? AND estado IN ('pendiente','confirmada') AND fecha_inicio < ? AND fecha_fin > ? LIMIT 1 FOR UPDATE");
            $overlap->execute([$salonId, $b->format('Y-m-d H:i:s'), $a->format('Y-m-d H:i:s')]);
            if ($overlap->fetch()) throw new \DomainException('El salón ya tiene una reserva pendiente o confirmada en ese horario.');
            $stmt = $pdo->prepare("INSERT INTO reservas (salon_id,usuario_id,titulo,solicitante,dependencia,descripcion,fecha_inicio,fecha_fin,estado) VALUES (?,?,?,?,?,?,?,?,?) RETURNING id");
            $stmt->execute([
                $salonId, (int)$user['id'], $title, $requester,
                trim((string)($data['dependencia'] ?? '')) ?: null,
                trim((string)($data['descripcion'] ?? '')) ?: null,
                $a->format('Y-m-d H:i:s'), $b->format('Y-m-d H:i:s'), 'pendiente'
            ]);
            $id = (int)$stmt->fetchColumn();

            // Vincular el equipamiento seleccionado a la reserva.
            $equipmentIds = $data['equipamiento_ids'] ?? [];
            if (!is_array($equipmentIds)) throw new \InvalidArgumentException('El equipamiento seleccionado no tiene un formato válido.');
            $equipmentIds = array_values(array_unique(array_filter(array_map(static fn($v) => filter_var($v, FILTER_VALIDATE_INT), $equipmentIds), static fn($v) => $v !== false && $v > 0)));

            // Permite resolver los nombres de referencia del formulario a registros del catálogo.
            $equipmentNames = $data['equipamiento_solicitado'] ?? [];
            if (!is_array($equipmentNames)) $equipmentNames = [];
            $findEquipment = $pdo->prepare('SELECT id FROM equipos WHERE LOWER(nombre)=LOWER(?) AND activo IS TRUE');
            foreach ($equipmentNames as $name) {
                $name = trim((string)$name);
                if ($name === '') continue;
                $findEquipment->execute([$name]);
                $equipmentId = $findEquipment->fetchColumn();
                if (!$equipmentId) throw new \InvalidArgumentException('El equipamiento "' . $name . '" no está registrado en el catálogo. Regístralo primero en Equipamiento.');
                $equipmentIds[] = (int)$equipmentId;
            }
            $equipmentIds = array_values(array_unique($equipmentIds));
            $checkEquipment = $pdo->prepare('SELECT id FROM equipos WHERE id=? AND activo IS TRUE');
            $linkEquipment = $pdo->prepare('INSERT INTO reserva_equipos (reserva_id,equipo_id,cantidad) VALUES (?,?,1) ON CONFLICT (reserva_id,equipo_id) DO NOTHING');
            foreach ($equipmentIds as $equipmentId) {
                $checkEquipment->execute([$equipmentId]);
                if (!$checkEquipment->fetchColumn()) throw new \InvalidArgumentException('Uno de los equipos seleccionados no existe o está inactivo.');
                $linkEquipment->execute([$id, $equipmentId]);
            }

            $pdo->commit();
            return $id;
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // PostgreSQL SQLSTATE 23P01 = violación de restricción EXCLUDE por solapamiento.
            if ((string)$e->getCode() === '23P01') {
                throw new \DomainException('El salón ya tiene una reserva pendiente o confirmada en ese horario.');
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
