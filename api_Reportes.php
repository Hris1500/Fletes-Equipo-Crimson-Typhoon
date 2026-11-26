<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/api.php'; // Reutiliza la conexión $pdo / $conn existente en dev

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin    = $_GET['fin']    ?? date('Y-m-d');
$estado = $_GET['estado'] ?? '';

try {
    $sql = "SELECT * FROM fletes WHERE DATE(fecha) BETWEEN :inicio AND :fin";
    $params = [':inicio' => $inicio, ':fin' => $fin];

    if ($estado !== '') {
        $sql .= " AND estado = :estado";
        $params[':estado'] = $estado;
    }

    $sql .= " ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'ok' => true,
        'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
