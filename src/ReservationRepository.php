<?php
declare(strict_types=1);

namespace App;

use PDO;

final class ReservationRepository
{
    public function __construct(private readonly PDO $db) {}

    public function list(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['estado'])) {
            $where[] = 'r.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = 'r.fecha_entrada >= :fecha_desde';
            $params['fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = 'r.fecha_salida <= :fecha_hasta';
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }
        if (!empty($filters['busqueda'])) {
            $where[] = 'r.nombre_huesped LIKE :busqueda';
            $params['busqueda'] = '%' . $filters['busqueda'] . '%';
        }

        $sql = 'SELECT r.* FROM reserva r' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY r.fecha_entrada ASC, r.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM reserva WHERE id = :id');
        $statement->execute(['id' => $id]);
        $reservation = $statement->fetch();
        return $reservation ?: null;
    }

    public function eventsFor(int $reservationId): array
    {
        $statement = $this->db->prepare('SELECT * FROM reserva_evento WHERE reserva_id = :id ORDER BY fecha_creacion ASC, id ASC');
        $statement->execute(['id' => $reservationId]);
        return $statement->fetchAll();
    }

    public function insert(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO reserva (nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas)
             VALUES (:nombre, :email, :alojamiento, :entrada, :salida, :estado, :importe, :notas)'
        );
        $statement->execute([
            'nombre' => $data['nombre_huesped'], 'email' => $data['email_huesped'],
            'alojamiento' => $data['nombre_alojamiento'], 'entrada' => $data['fecha_entrada'],
            'salida' => $data['fecha_salida'], 'estado' => 'pendiente',
            'importe' => $data['importe'], 'notas' => $data['notas'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function addEvent(int $reservationId, string $type, string $description): void
    {
        $statement = $this->db->prepare('INSERT INTO reserva_evento (reserva_id, tipo, descripcion) VALUES (:id, :tipo, :descripcion)');
        $statement->execute(['id' => $reservationId, 'tipo' => $type, 'descripcion' => $description]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->db->prepare('UPDATE reserva SET estado = :estado WHERE id = :id');
        $statement->execute(['estado' => $status, 'id' => $id]);
    }
}
