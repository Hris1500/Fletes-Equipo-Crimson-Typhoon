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

// API UNIDADES

function get_unidades($pdo, $id = null) {
    if ($id !== null) {
        $stmt = $pdo->prepare("SELECT * FROM unidades WHERE id_unidad = :id");
        $stmt->execute([':id' => $id]);
        $unidad = $stmt->fetch();

        if ($unidad) {
            responder(200, $unidad);
        } else {
            responder(404, ["mensaje" => "Unidad no encontrada"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM unidades");
        $unidades = $stmt->fetchAll();
        responder(200, $unidades);
    }
}


function create_unidad($pdo, $data) {
    $placas = trim((string) ($data['placas'] ?? ''));
    $tipo = trim((string) ($data['tipo'] ?? ''));

    if (empty($placas) || empty($tipo) || !isset($data['capacidad_kg'])
            || !is_numeric($data['capacidad_kg'])
            || !is_finite((float) $data['capacidad_kg'])
            || (float) $data['capacidad_kg'] <= 0
        ) {
        responder(400, ["error" => "Datos incompletos o invalidos."]);
        return;
    }

    $sql = "INSERT INTO unidades (placas, tipo, capacidad_kg) VALUES (:placas, :tipo, :capacidad_kg)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':placas' => $placas,
        ':tipo' => $tipo,
        ':capacidad_kg' => $data['capacidad_kg']
    ]);

    responder(201, [
        "mensaje" => "Unidad creada con éxito",
        "id_unidad" => $pdo->lastInsertId()
    ]);
}


function update_unidad($pdo, $id, $data) {
    $placas = trim((string) ($data['placas'] ?? ''));
    $tipo = trim((string) ($data['tipo'] ?? ''));

    if ($id === null) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    if (empty($placas) || empty($tipo) || !isset($data['capacidad_kg'])
            || !is_numeric($data['capacidad_kg'])
            || !is_finite((float) $data['capacidad_kg'])
            || (float) $data['capacidad_kg'] <= 0
        ) {
        responder(400, ["error" => "Datos incompletos o erróneos para actualizar."]);
        return;
    }

    $sql_id = "SELECT * FROM unidades WHERE id_unidad = :id";
    $stmt_id = $pdo->prepare($sql_id);
    $stmt_id->execute([':id' => $id]);
    $unidad = $stmt_id->fetch();

    if (!$unidad) {
        responder(404, ["error" => "Unidad no encontrada"]);
        return;
    }

    $sql = "UPDATE unidades SET placas = :placas, tipo = :tipo, capacidad_kg = :capacidad_kg WHERE id_unidad = :id_unidad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':placas' => $placas,
        ':tipo' => $tipo,
        ':capacidad_kg' => $data['capacidad_kg'],
        ':id_unidad' => $id
    ]);

    responder(200, ["mensaje" => "Unidad actualizada con éxito"]);

}

function delete_unidad($pdo, $id) {
    if ($id === null) {
        responder(400, ["error" => "Se requiere el parámetro 'id' en la URL."]);
        return;
    }

    $sql_id = "SELECT * FROM unidades WHERE id_unidad = :id";
    $stmt_id = $pdo->prepare($sql_id);
    $stmt_id->execute([':id' => $id]);
    $unidad = $stmt_id->fetch();

    if (!$unidad) {
        responder(404, ["error" => "Unidad no encontrada"]);
        return;
    }

    $sql_flete = "SELECT 1 FROM fletes WHERE id_unidad = :id LIMIT 1";
    $stmt_flete = $pdo->prepare($sql_flete);
    $stmt_flete->execute([':id' => $id]);
    $flete = $stmt_flete->fetch();

    if ($flete) {
        $sql = "UPDATE unidades SET activa = 0 WHERE id_unidad = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        responder(200, ["mensaje" => "Unidad desactivada con éxito, ya que tiene fletes asociados"]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM unidades WHERE id_unidad = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        responder(200, ["mensaje" => "Unidad eliminada con éxito"]);
    } else {
        responder(404, ["error" => "Unidad no encontrada"]);
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
