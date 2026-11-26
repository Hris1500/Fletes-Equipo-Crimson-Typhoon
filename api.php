<?php
header('Content-Type: application/json; charset=utf-8');

$dbFile = __DIR__ . '/fletes.db';
if (!file_exists($dbFile)) {
    require_once __DIR__ . '/setup.php';
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action =$_GET['action'] ?? '';

switch ($action) {
    case 'reportes_fletes':
        $inicio    =$_GET['inicio'] ?? date('Y-m-01');
        $fin       =$_GET['fin']    ?? date('Y-m-d');
        $estado    =$_GET['estado'] ?? '';
        $clienteId =$_GET['cliente_id'] ?? '';
        $choferId  =$_GET['chofer_id'] ?? '';

        $sql = "
            SELECT 
                f.id,
                f.fecha,
                f.origen,
                f.destino,
                COALESCE(c.nombre, 'Sin cliente') AS cliente,
                COALESCE(u.placas, 'Sin asignar') AS unidad,
                COALESCE(ch.nombre, 'Sin asignar') AS chofer,
                f.monto,
                f.estado
            FROM fletes f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            LEFT JOIN unidades u ON f.unidad_id = u.id
            LEFT JOIN choferes ch ON f.chofer_id = ch.id
            WHERE date(f.fecha) BETWEEN :inicio AND :fin
        ";

        $params = [':inicio' => $inicio, ':fin' =>$fin];

        if ($estado !== '') {$sql .= " AND f.estado = :estado";
            $params[':estado'] =$estado;
        }
        if ($clienteId !== '') {$sql .= " AND f.cliente_id = :cliente_id";
            $params[':cliente_id'] =$clienteId;
        }
        if ($choferId !== '') {$sql .= " AND f.chofer_id = :chofer_id";
            $params[':chofer_id'] =$choferId;
        }

        $sql .= " ORDER BY f.fecha DESC, f.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'ok' => true,
            'registros' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        break;

    case 'catalogos_reportes':
        echo json_encode([
            'ok' => true,
            'clientes' => $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
            'choferes' => $pdo->query("SELECT id, nombre FROM choferes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
