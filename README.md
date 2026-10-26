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

Como alternativa, con Docker Desktop abierto se puede levantar todo, incluida la base de datos:

```bash
docker compose up --build
```

La aplicación quedará en `http://localhost:8000/reservas` y MySQL estará disponible en el puerto local `3307`.

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

La aplicación incluye `Dockerfile` y está preparada para un servicio que permita desplegar contenedores y configurar una base MySQL/MariaDB externa. El document root del hosting PHP tradicional debe apuntar a `public/`.

Variables necesarias en producción:

```text
DB_HOST=...
DB_PORT=3306
DB_NAME=...
DB_USER=...
DB_PASSWORD=...
```

Después de crear la base de datos, importar `database/schema.sql` y configurar esas variables, comprobar `/reservas` y `/api/reservas`. No se debe subir `.env` al repositorio.

### HOKAS (opcion recomendada para esta demo)

HOKAS ofrece un servidor PHP gratuito con MySQL, variables de entorno, HTTPS y despliegue desde GitHub. La cuenta gratuita tiene recursos limitados y no garantiza disponibilidad continua, por lo que se utiliza únicamente como demo de la prueba técnica.

1. Crear una cuenta en [HOKAS](https://hokas.net/sign-up).
2. Crear un servidor de tipo **PHP Server** en el plan gratuito.
3. Conectar el repositorio `davidarrcal/Api-gesti-n-de-reservas` desde GitHub.
4. Configurar el document root en `public/` y ejecutar `composer install` desde la terminal del servidor si el panel no lo hace automáticamente.
5. Crear una base MySQL desde el panel y guardar sus credenciales.
6. Importar `database/schema.sql` desde la herramienta MySQL/phpMyAdmin del panel.
7. Añadir como variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.
8. Reiniciar o redeployar el servidor y abrir el subdominio HTTPS proporcionado.
9. Comprobar `/health`, `/reservas` y `/api/reservas`.

La URL pública se añadirá aquí después de crear el servidor:

`Demo: pendiente de configurar`

### Railway (alternativa)

1. Crear un proyecto nuevo en Railway y añadir un servicio MySQL.
2. Añadir el repositorio de GitHub como servicio de aplicación. Railway detectará el `Dockerfile`.
3. En el servicio de aplicación, configurar las variables `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER` y `MYSQLPASSWORD` usando los valores del servicio MySQL. La aplicación también acepta los nombres `DB_*`.
4. Ejecutar una importación de `database/schema.sql` contra el servicio MySQL. Puede hacerse desde un cliente local usando las credenciales públicas de Railway.
5. Generar un dominio público para el servicio web.
6. Comprobar `/health`, `/reservas` y `/api/reservas`.

El endpoint `/health` verifica que PHP puede conectarse a la base de datos y está configurado como health check del servicio.

Railway sigue siendo una alternativa válida si vuelve a permitir registros nuevos.
