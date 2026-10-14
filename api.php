
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');


function responder(int $estado, array $datos): never
{
    http_response_code($estado);

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}
function leerJson(): array
{
    $contenido = file_get_contents('php://input');

    $datos = json_decode($contenido ?: '', true);

    if (!is_array($datos)) {
        responder(400, [
            'error' => 'El cuerpo JSON no es válido.'
        ]);
    }

    return $datos;
}

try {
    // RUTA DE LA BASE DE DATOS
    $rutaBase = __DIR__ . '/database/fletes.sqlite';

    $directorio = dirname($rutaBase);

    if (!is_dir($directorio)) {
        mkdir($directorio, 0775, true);
    }

    $db = new PDO('sqlite:' . $rutaBase);

    $db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $db->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    $metodo = $_SERVER['REQUEST_METHOD'];

    $recurso = $_GET['recurso'] ?? '';

    $id = null;

    if (isset($_GET['id'])) {
        $id = filter_var(
            $_GET['id'],
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id < 1) {
            responder(400, [
                'error' => 'El identificador no es válido.'
            ]);
        }
    }

    switch ($recurso) {
        default:
            responder(404, [
                'error' => 'El recurso solicitado no existe.'
            ]);
    }

} catch (Throwable $error) {
    error_log($error->getMessage());

    responder(500, [
        'error' => 'Error interno del servidor.'
    ]);
}
