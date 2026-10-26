<?php
declare(strict_types=1);

namespace App;

use App\Http\BadRequestException;
use DateTimeImmutable;

final class ReservationValidator
{
    private const STATUSES = ['pendiente', 'confirmada', 'cancelada'];

    public function validateCreation(array $input): array
    {
        $errors = [];
        $required = ['nombre_huesped', 'email_huesped', 'nombre_alojamiento', 'fecha_entrada', 'fecha_salida', 'importe'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = 'Este campo es obligatorio.';
            }
        }

        $email = (string) ($input['email_huesped'] ?? '');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email_huesped'] = 'El email no es valido.';
        }
        $start = $this->date($input['fecha_entrada'] ?? '');
        $end = $this->date($input['fecha_salida'] ?? '');
        if (!$start) $errors['fecha_entrada'] = 'La fecha de entrada no es valida.';
        if (!$end) $errors['fecha_salida'] = 'La fecha de salida no es valida.';
        if ($start && $end && $end <= $start) $errors['fecha_salida'] = 'Debe ser posterior a la fecha de entrada.';

        $amount = $input['importe'] ?? null;
        if (!is_numeric($amount) || (float) $amount < 0) $errors['importe'] = 'El importe debe ser un numero no negativo.';
        foreach (['nombre_huesped' => 120, 'email_huesped' => 180, 'nombre_alojamiento' => 160] as $field => $max) {
            if (isset($input[$field]) && mb_strlen(trim((string) $input[$field])) > $max) $errors[$field] = "No puede superar {$max} caracteres.";
        }
        if (isset($input['notas']) && mb_strlen((string) $input['notas']) > 2000) $errors['notas'] = 'No puede superar 2000 caracteres.';
        if ($errors) throw new BadRequestException('Hay errores de validacion.', $errors);

        return [
            'nombre_huesped' => trim((string) $input['nombre_huesped']),
            'email_huesped' => trim($email),
            'nombre_alojamiento' => trim((string) $input['nombre_alojamiento']),
            'fecha_entrada' => (string) $input['fecha_entrada'], 'fecha_salida' => (string) $input['fecha_salida'],
            'importe' => number_format((float) $amount, 2, '.', ''), 'notas' => trim((string) ($input['notas'] ?? '')) ?: null,
        ];
    }

    public function validateStatus(mixed $status): string
    {
        if (!is_string($status) || !in_array($status, self::STATUSES, true)) {
            throw new BadRequestException('Estado no valido.', ['estado' => 'Usa pendiente, confirmada o cancelada.']);
        }
        return $status;
    }

    public function validateFilters(array $filters): array
    {
        $allowedStatuses = self::STATUSES;
        foreach (['estado', 'fecha_desde', 'fecha_hasta', 'busqueda'] as $field) {
            if (isset($filters[$field]) && !is_string($filters[$field])) {
                throw new BadRequestException('Los filtros no son validos.', [$field => 'Debe ser un valor de texto.']);
            }
        }
        if (isset($filters['estado']) && $filters['estado'] !== '' && !in_array($filters['estado'], $allowedStatuses, true)) {
            throw new BadRequestException('Los filtros no son validos.', ['estado' => 'Estado no valido.']);
        }
        foreach (['fecha_desde', 'fecha_hasta'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '' && !$this->date($filters[$field])) {
                throw new BadRequestException('Los filtros no son validos.', [$field => 'Usa el formato YYYY-MM-DD.']);
            }
        }
        if (($filters['fecha_desde'] ?? '') !== '' && ($filters['fecha_hasta'] ?? '') !== ''
            && $this->date($filters['fecha_desde']) > $this->date($filters['fecha_hasta'])) {
            throw new BadRequestException('Los filtros no son validos.', ['fecha_hasta' => 'Debe ser posterior a fecha_desde.']);
        }
        if (isset($filters['busqueda']) && mb_strlen((string) $filters['busqueda']) > 120) {
            throw new BadRequestException('Los filtros no son validos.', ['busqueda' => 'No puede superar 120 caracteres.']);
        }
        return array_intersect_key($filters, array_flip(['estado', 'fecha_desde', 'fecha_hasta', 'busqueda']));
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
