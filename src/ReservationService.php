<?php
declare(strict_types=1);

namespace App;

use App\Http\BadRequestException;
use App\Http\NotFoundException;
use PDO;

final class ReservationService
{
    public function __construct(private readonly PDO $db, private readonly ReservationRepository $repository, private readonly ReservationValidator $validator) {}

    public function list(array $filters): array { return $this->repository->list($this->validator->validateFilters($filters)); }

    public function detail(int $id): array
    {
        $reservation = $this->repository->find($id);
        if (!$reservation) throw new NotFoundException('Reserva no encontrada.');
        $reservation['eventos'] = $this->repository->eventsFor($id);
        return $reservation;
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreation($input);
        $this->db->beginTransaction();
        try {
            $id = $this->repository->insert($data);
            $this->repository->addEvent($id, 'creacion', 'Reserva creada');
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
        return $this->detail($id);
    }

    public function changeStatus(int $id, mixed $newStatus): array
    {
        $status = $this->validator->validateStatus($newStatus);
        $reservation = $this->repository->find($id);
        if (!$reservation) throw new NotFoundException('Reserva no encontrada.');
        if ($reservation['estado'] === $status) throw new BadRequestException('La reserva ya tiene ese estado.');
        if ($reservation['estado'] === 'cancelada') throw new BadRequestException('Una reserva cancelada no puede modificarse.');

        $this->db->beginTransaction();
        try {
            $this->repository->updateStatus($id, $status);
            $this->repository->addEvent($id, 'cambio_estado', 'Reserva ' . $status);
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
        return $this->detail($id);
    }
}
