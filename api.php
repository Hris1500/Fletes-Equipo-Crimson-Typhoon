
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
        //API Dashboard y KPIs
        case 'dashboard':
            if ($metodo === 'GET') {
                $stmt = $db->query("
                    SELECT 
                        COUNT(*) as total_fletes,
                        SUM(CASE WHEN estatus = 'en_transito' THEN 1 ELSE 0 END) as en_transito,
                        SUM(CASE WHEN estatus = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                        SUM(CASE WHEN estatus = 'finalizado' THEN 1 ELSE 0 END) as finalizados
                    FROM fletes
                ");
                responder(200, $stmt->fetch());
            }
            break;

        //Ver el historial completo de fletes
        case 'historial':
            if ($metodo === 'GET') {
                // Hacemos JOIN para traer los nombres en lugar de solo los IDs
                $stmt = $db->query("
                    SELECT f.id_flete, f.origen, f.destino, f.estatus, f.fecha_salida, 
                           c.nombre as cliente, ch.nombre as chofer, u.placas
                    FROM fletes f
                    JOIN clientes c ON f.id_cliente = c.id_cliente
                    JOIN choferes ch ON f.id_chofer = ch.id_chofer
                    JOIN unidades u ON f.id_unidad = u.id_unidad
                    ORDER BY f.id_flete DESC
                ");
                responder(200, $stmt->fetchAll());
            }
            break;

        //API Reportes de ingresos
        case 'reportes':
            if ($metodo === 'GET') {
                $stmt = $db->query("
                    SELECT 
                        SUM(monto) as ganancias_totales,
                        SUM(CASE WHEN pagado = 1 THEN monto ELSE 0 END) as cobrado,
                        SUM(CASE WHEN pagado = 0 THEN monto ELSE 0 END) as por_cobrar
                    FROM fletes
                ");
                responder(200, $stmt->fetch());
            }
            break;

        default:
            responder(404, ['error' => 'El recurso solicitado no existe.']);
    }

} catch (Throwable $error) {
    error_log($error->getMessage());

    responder(500, [
        'error' => 'Error interno del servidor.'
    ]);
}
