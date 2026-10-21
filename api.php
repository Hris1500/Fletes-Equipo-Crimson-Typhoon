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

        // Poner que corra setup si tabla no existe

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

// API CHOFERES

function get_choferes($pdo, $id = null) {
    if ($id !== null) {
        $stmt = $pdo->prepare("SELECT * FROM choferes WHERE id_chofer = :id");
        $stmt->execute([':id' => $id]);
        $chofer = $stmt->fetch();

        if ($chofer) {
            responder(200, $chofer);
        } else {
            responder(404, ["mensaje" => "Chofer no encontrado"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM choferes");
        $choferes = $stmt->fetchAll();
        responder(200, $choferes);
    }
}


function create_chofer($pdo, $data) {
    if (empty($data['nombre']) || !isset($data['telefono']) || !isset($data['num_licencia'])) {
        responder(400, ["error" => "Datos incompletos."]);
        return;
    }

    $sql = "INSERT INTO choferes (nombre, telefono, num_licencia) VALUES (:nombre, :telefono, :num_licencia)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'],
        ':num_licencia' => $data['num_licencia']
    ]);

    responder(201, [
        "mensaje" => "Chofer creado con éxito",
        "id_chofer" => $pdo->lastInsertId()
    ]);
}


function update_chofer($pdo, $id, $data) {
    if ($id === null) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (empty($data['nombre']) || !isset($data['telefono']) || !isset($data['num_licencia'])) {
        responder(400, ["error" => "Datos incompletos para actualizar."]);
        return;
    }

    $sql_id = "SELECT * FROM choferes WHERE id_chofer = :id";
    $stmt_id = $pdo->prepare($sql_id);
    $stmt_id->execute([':id' => $id]);
    $chofer = $stmt_id->fetch();

    if (!$chofer) {
        responder(404, ["error" => "Chofer no encontrado"]);
        return;
    }

    $sql = "UPDATE choferes SET nombre = :nombre, telefono = :telefono, num_licencia = :num_licencia WHERE id_chofer = :id_chofer";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'],
        ':num_licencia' => $data['num_licencia'],
        ':id_chofer' => $id
    ]);

    responder(200, ["mensaje" => "Chofer actualizado con éxito"]);

}

function delete_chofer($pdo, $id) {
    if ($id === null) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $sql_id = "SELECT * FROM choferes WHERE id_chofer = :id";
    $stmt_id = $pdo->prepare($sql_id);
    $stmt_id->execute([':id' => $id]);
    $chofer = $stmt_id->fetch();

    if (!$chofer) {
        responder(404, ["error" => "Chofer no encontrado"]);
        return;
    }

    $sql_flete = "SELECT 1 FROM fletes WHERE id_chofer = :id LIMIT 1";
    $stmt_flete = $pdo->prepare($sql_flete);
    $stmt_flete->execute([':id' => $id]);
    $flete = $stmt_flete->fetch();

    if ($flete) {
        $sql = "UPDATE choferes SET activo = 0 WHERE id_chofer = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        responder(200, ["mensaje" => "Chofer desactivado con éxito, ya que tiene fletes asociados"]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM choferes WHERE id_chofer = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Chofer eliminado con éxito"]);
    } else {
        responder(404, ["error" => "Chofer no encontrado"]);
    }
}


// ENRUTADOR Y CONTROLADOR PRINCIPAL

$pdo = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        get_choferes($pdo, $id);
        break;

    case 'POST':
        create_chofer($pdo, $input);
        break;

    case 'PUT':
        update_chofer($pdo, $id, $input);
        break;

    default:
        responder(451, ["error" => "Método HTTP no permitido"]);
        break;
}
