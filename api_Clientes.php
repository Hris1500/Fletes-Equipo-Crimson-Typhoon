<?php
// ==========================================
// 1. CONFIGURACIÓN DE CORS Y CABECERAS HTTP
// ==========================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de la petición Preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==========================================
// 2. CONEXIÓN A LA BASE DE DATOS (PDO)
// ==========================================
function get_db_connection() {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/database.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Crear la tabla si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            precio REAL NOT NULL,
            existencia INTEGER NOT NULL
        )");

        return $pdo;
    } catch (PDOException $e) {
        responder(500, ["error" => "Error de conexión a la base de datos: " . $e->getMessage()]);
        exit();
    }
}

// Función auxiliar para responder en formato JSON
function responder($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// ==========================================
// 3. FUNCIONES PARA CADA MÉTODO HTTP
// ==========================================

/**
 * Maneja las peticiones GET (Obtener todos o uno por ID)
 */
function get_productos($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch();

        if ($producto) {
            responder(200, $producto);
        } else {
            responder(404, ["mensaje" => "Producto no encontrado"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM productos");
        $productos = $stmt->fetchAll();
        responder(200, $productos);
    }
}

/**
 * Maneja las peticiones POST (Crear nuevo producto)
 */
function create_producto($pdo, $data) {
    if (empty($data['nombre']) || !isset($data['precio']) || !isset($data['existencia'])) {
        responder(400, ["error" => "Datos incompletos. Se requiere nombre, precio y existencia."]);
        return;
    }

    $sql = "INSERT INTO productos (nombre, precio, existencia) VALUES (:nombre, :precio, :existencia)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':precio' => $data['precio'],
        ':existencia' => $data['existencia']
    ]);

    responder(201, [
        "mensaje" => "Producto creado con éxito",
        "id" => $pdo->lastInsertId()
    ]);
}

/**
 * Maneja las peticiones PUT (Actualizar producto existente)
 */
function update_producto($pdo, $id, $data) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (empty($data['nombre']) || !isset($data['precio']) || !isset($data['existencia'])) {
        responder(400, ["error" => "Datos incompletos para actualizar."]);
        return;
    }

    $sql = "UPDATE productos SET nombre = :nombre, precio = :precio, existencia = :existencia WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':precio' => $data['precio'],
        ':existencia' => $data['existencia'],
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Producto actualizado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Producto no encontrado o sin cambios"]);
    }
}

/**
 * Maneja las peticiones DELETE (Eliminar producto por ID)
 */
function delete_producto($pdo, $id) {
    if (!$id) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Producto eliminado con éxito"]);
    } else {
        responder(404, ["mensaje" => "Producto no encontrado"]);
    }
}

// ==========================================
// 4. ENRUTADOR Y CONTROLADOR PRINCIPAL
// ==========================================
$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        get_productos($pdo, $id);
        break;

    case 'POST':
        create_producto($pdo, $input);
        break;

    case 'PUT':
        update_producto($pdo, $id, $input);
        break;

    case 'DELETE':
        delete_producto($pdo, $id);
        break;

    default:
        responder(451, ["error" => "Método HTTP no permitido"]);
        break;
}