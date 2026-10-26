<?php
declare(strict_types=1);

use App\Database;
use App\Http\BadRequestException;
use App\Http\JsonResponse;
use App\Http\NotFoundException;
use App\ReservationRepository;
use App\ReservationService;
use App\ReservationValidator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

loadEnvironment(dirname(__DIR__) . '/.env');
$securityHeaders = [
    'X-Content-Type-Options: nosniff',
    'X-Frame-Options: DENY',
    'Referrer-Policy: no-referrer',
];
foreach ($securityHeaders as $securityHeader) header($securityHeader);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $db = Database::connect();
    $service = new ReservationService($db, new ReservationRepository($db), new ReservationValidator());

    if ($path === '/health') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if (str_starts_with($path, '/api/')) {
        handleApi($service, $path, $method);
    }

    $twig = new Environment(new FilesystemLoader(dirname(__DIR__) . '/templates'), [
        'cache' => false,
        'autoescape' => 'html',
    ]);
    if ($path === '/' || $path === '/reservas') {
        echo $twig->render('reservas/index.html.twig', ['reservas' => $service->list($_GET), 'filtros' => $_GET]);
        exit;
    }
    if ($path === '/reservas/nueva') {
        echo $twig->render('reservas/new.html.twig');
        exit;
    }
    if (preg_match('#^/reservas/(\d+)$#', $path, $matches)) {
        echo $twig->render('reservas/show.html.twig', ['reserva' => $service->detail((int) $matches[1])]);
        exit;
    }
    http_response_code(404);
    echo $twig->render('error.html.twig', ['status' => 404, 'message' => 'Pagina no encontrada.']);
} catch (BadRequestException $exception) {
    JsonResponse::send(['error' => $exception->getMessage(), 'detalles' => $exception->errors], $exception->errors ? 422 : 400);
} catch (NotFoundException $exception) {
    if (str_starts_with($path, '/api/')) JsonResponse::send(['error' => $exception->getMessage()], 404);
    http_response_code(404);
    echo 'Reserva no encontrada.';
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    if (str_starts_with($path, '/api/')) JsonResponse::send(['error' => 'Error interno del servidor.'], 500);
    http_response_code(500);
    echo 'No se ha podido cargar la aplicacion.';
}

function handleApi(ReservationService $service, string $path, string $method): never
{
    header('Cache-Control: no-store');
    if ($path === '/api/reservas' && $method === 'GET') {
        JsonResponse::send(['datos' => $service->list($_GET)]);
    }
    if ($path === '/api/reservas' && $method === 'POST') {
        requireJsonContentType();
        $body = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) throw new BadRequestException('El cuerpo debe ser JSON valido.');
        JsonResponse::send(['datos' => $service->create($body)], 201);
    }
    if (preg_match('#^/api/reservas/(\d+)$#', $path, $matches) && $method === 'GET') {
        JsonResponse::send(['datos' => $service->detail((int) $matches[1])]);
    }
    if (preg_match('#^/api/reservas/(\d+)/estado$#', $path, $matches) && $method === 'PATCH') {
        requireJsonContentType();
        $body = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) throw new BadRequestException('El cuerpo debe ser JSON valido.');
        JsonResponse::send(['datos' => $service->changeStatus((int) $matches[1], $body['estado'] ?? null)]);
    }
    JsonResponse::send(['error' => 'Ruta o metodo no permitido.'], $path === '/api/reservas' ? 405 : 404);
}

function requireJsonContentType(): void
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!preg_match('#^application/json(?:\s*;|$)#i', $contentType)) {
        throw new BadRequestException('Content-Type debe ser application/json.');
    }
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 65536) throw new BadRequestException('El cuerpo de la peticion es demasiado grande.');
}

function loadEnvironment(string $filename): void
{
    if (!is_file($filename)) return;
    foreach (file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
    }
}
