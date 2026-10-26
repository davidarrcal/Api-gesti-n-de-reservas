# Gestión de reservas

Aplicación de prueba técnica para gestionar reservas de alojamiento con PHP nativo, MySQL, Twig y JavaScript vanilla. No utiliza framework de aplicación.

## Requisitos

- PHP 8.1 o superior con `pdo_mysql` y `mbstring`.
- MySQL/MariaDB.
- Composer.

## Puesta en marcha local

1. Instalar dependencias:

   ```bash
   composer install
   ```

2. Crear la configuración y editarla si es necesario:

   ```bash
   cp .env.example .env
   ```

   En Windows se puede duplicar `.env.example` manualmente como `.env`.

3. Importar `database/schema.sql` desde MySQL/MariaDB, phpMyAdmin o la consola. El script crea la base de datos, las tablas y datos de ejemplo.

4. Arrancar el servidor:

   ```bash
   composer serve
   ```

   Abrir `http://localhost:8000/reservas`.

## API

Todas las respuestas tienen la forma `{ "datos": ... }` cuando son correctas o `{ "error": ..., "detalles": ... }` cuando hay un error.

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/api/reservas` | Listado; filtros `estado`, `fecha_desde`, `fecha_hasta`, `busqueda` |
| GET | `/api/reservas/{id}` | Detalle con historial |
| POST | `/api/reservas` | Crear reserva |
| PATCH | `/api/reservas/{id}/estado` | Cambiar a `pendiente`, `confirmada` o `cancelada` |

Ejemplo de creación:

```json
{
  "nombre_huesped": "Laura Sanchez",
  "email_huesped": "laura@example.com",
  "nombre_alojamiento": "Hotel Central",
  "fecha_entrada": "2026-09-10",
  "fecha_salida": "2026-09-12",
  "importe": 180.50,
  "notas": "Cama supletoria"
}
```

## Decisiones técnicas y seguridad

- PDO usa consultas preparadas, `utf8mb4`, excepciones y emulación de prepares desactivada.
- La validación se ejecuta en `ReservationValidator`, independientemente de la interfaz.
- Las operaciones de escritura y el evento de historial se ejecutan en una misma transacción.
- Twig tiene autoescape HTML activado; no se utiliza `|raw`.
- Los mensajes internos de excepciones no se exponen en respuestas 500. Los valores recibidos se limitan y se validan antes de persistir.
- La interfaz escapa los datos recibidos antes de insertarlos en la tabla mediante JavaScript.
- En producción añadiría autenticación/autorización, CSRF para sesiones, HTTPS, rate limiting y una política CORS explícita si hubiera frontend separado.

## Tests

```bash
composer test
```

Los tests cubren normalización, fechas, importes y estados permitidos.

## Mejoras con más tiempo

Añadiría autenticación de usuarios, paginación, disponibilidad del alojamiento, tests de integración con una base MySQL temporal, logging estructurado y migraciones versionadas.

Para 1000 reservas por hora mantendría índices sobre filtros, paginaría siempre el listado, separaría lecturas y escrituras si fuera necesario, incorporaría cache para consultas repetidas y procesaría notificaciones/eventos secundarios mediante una cola. También monitorizaría tiempos de consulta y errores antes de escalar horizontalmente.

## Despliegue

No se incluye una URL pública en este entorno. La aplicación está preparada para un hosting PHP que permita configurar variables de entorno, ejecutar Composer y conectar con MySQL. El document root debe apuntar a `public/`.
