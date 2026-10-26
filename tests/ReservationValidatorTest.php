<?php
declare(strict_types=1);

namespace Tests;

use App\Http\BadRequestException;
use App\ReservationValidator;
use PHPUnit\Framework\TestCase;

final class ReservationValidatorTest extends TestCase
{
    private ReservationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ReservationValidator();
    }

    public function testValidReservationIsNormalised(): void
    {
        $result = $this->validator->validateCreation([
            'nombre_huesped' => '  Lucia Martin ', 'email_huesped' => 'lucia@example.com',
            'nombre_alojamiento' => 'Hotel Norte', 'fecha_entrada' => '2026-06-01',
            'fecha_salida' => '2026-06-04', 'importe' => '250', 'notas' => '',
        ]);
        self::assertSame('Lucia Martin', $result['nombre_huesped']);
        self::assertSame('250.00', $result['importe']);
        self::assertNull($result['notas']);
    }

    public function testExitDateMustBeAfterEntryDate(): void
    {
        $this->expectException(BadRequestException::class);
        $this->validator->validateCreation([
            'nombre_huesped' => 'Lucia', 'email_huesped' => 'bad', 'nombre_alojamiento' => 'Hotel',
            'fecha_entrada' => '2026-06-05', 'fecha_salida' => '2026-06-04', 'importe' => -1,
        ]);
    }

    public function testOnlyKnownStatusesAreAccepted(): void
    {
        $this->expectException(BadRequestException::class);
        $this->validator->validateStatus('borrador');
    }

    public function testFiltersRejectInvalidDateRange(): void
    {
        $this->expectException(BadRequestException::class);
        $this->validator->validateFilters(['fecha_desde' => '2026-07-10', 'fecha_hasta' => '2026-07-01']);
    }

    public function testFiltersKeepOnlySupportedFields(): void
    {
        self::assertSame(['estado' => 'pendiente'], $this->validator->validateFilters([
            'estado' => 'pendiente', 'otro_filtro' => 'ignorado',
        ]));
    }
}
