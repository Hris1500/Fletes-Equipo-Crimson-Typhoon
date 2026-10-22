<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function get_db_connection() {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    } catch (PDOException $e) {
        responder(500, ["error" => "Error de conexión a la base de datos: " . $e->getMessage()]);
        exit();
    }
}

function responder($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// API CLIENTES

function get_clientes($pdo, $id = null) {
    if ($id !== null) {
        $stmt = $pdo->prepare("SELECT id_cliente, nombre, telefono, correo, direccion FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            responder(200, $cliente);
        } else {
            responder(404, ["mensaje" => "Cliente no encontrado"]);
        }
    } else {
        $stmt = $pdo->query("SELECT id_cliente, nombre, telefono, correo, direccion FROM clientes ORDER BY id_cliente");
        $clientes = $stmt->fetchAll();
        responder(200, $clientes);
    }
}

function create_cliente($pdo, $data) {
    if (!is_array($data)) {
        responder(400, ["error" => "Datos de entrada no válidos"]);
        return;
    }

    if (!isset($data['nombre']) || !is_string($data['nombre'])) {
        responder(400, ["error" => "El campo 'nombre' es obligatorio y debe ser texto."]);
        return;
    }

    $nombre = trim($data['nombre']);

    if ($nombre === '') {
        responder(400, ["error" => "El campo 'nombre' no puede estar vacío."]);
        return;
    }

    $campos = ['telefono', 'correo', 'direccion'];

    foreach ($campos as $campo) {
        if (!isset($data[$campo]) || !is_string($data[$campo])) {
            responder(400, ["error" => "El campo '$campo' es obligatorio y debe ser texto."]);
            return;
        }
    }

    $correo = trim($data['correo']);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responder(400, ["error" => "El campo 'correo' debe tener un formato válido."]);
        return;
    }

    $sql = "INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES (:nombre, :telefono, :correo, :direccion)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':telefono' => trim($data['telefono']),
        ':correo' => $correo,
        ':direccion' => trim($data['direccion'])
    ]);
    responder(201, ["mensaje" => "Cliente creado con éxito", "id" => $pdo->lastInsertId()]);
}

function update_cliente($pdo, $id, $data) {
    if ($id === null) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (!is_array($data)) {
        responder(400, ["error" => "Datos de entrada no válidos"]);
        return;
    }

    if (!isset($data['nombre']) || !is_string($data['nombre'])) {
        responder(400, ["error" => "El campo 'nombre' es obligatorio y debe ser texto."]);
        return;
    }

    $nombre = trim($data['nombre']);

    if ($nombre === '') {
        responder(400, ["error" => "El campo 'nombre' no puede estar vacío."]);
        return;
    }

    $campos = ['telefono', 'correo', 'direccion'];

    foreach ($campos as $campo) {
        if (!isset($data[$campo]) || !is_string($data[$campo])) {
            responder(400, ["error" => "El campo '$campo' es obligatorio y debe ser texto."]);
            return;
        }
    }

    $correo = trim($data['correo']);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responder(400, ["error" => "El campo 'correo' debe tener un formato válido."]);
        return;
    }

    $sql = "UPDATE clientes SET nombre = :nombre, telefono = :telefono, correo = :correo, direccion = :direccion WHERE id_cliente = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':telefono' => trim($data['telefono']),
        ':correo' => $correo,
        ':direccion' => trim($data['direccion']),
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Cliente actualizado con éxito"]);
        return;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM clientes WHERE id_cliente = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->fetch()) {
        responder(200, ["mensaje" => "No se realizaron cambios en el cliente"]);
    } else {
        responder(404, ["mensaje" => "Cliente no encontrado"]);
    }

}


function delete_cliente($pdo, $id) {
    if ($id === null) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Cliente eliminado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Cliente no encontrado"]);
    }
}

// ENRUTADOR Y CONTROLADOR PRINCIPAL

$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        get_clientes($pdo, $id);
        break;

    case 'POST':
        create_cliente($pdo, $input);
        break;

    case 'PUT':
        update_cliente($pdo, $id, $input);
        break;

    case 'DELETE':
        delete_cliente($pdo, $id);
        break;

    default:
        responder(405, ["error" => "Método HTTP no permitido"]);
        break;
}
