
<?php

header("Content-Type: application/json; charset=UTF-8");

function responderCU($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function conectarCU() {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON");
        return $pdo;
    } catch (PDOException $e) {
        responderCU(500, ["error" => "No se pudo conectar con SQLite."]);
    }
}

function leerEntradaCU() {
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) {
        responderCU(400, ["error" => "El JSON enviado no es válido."]);
    }

    return $datos;
}

function validarIdCU($id) {
    if ($id === null || filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
        responderCU(400, ["error" => "Se requiere un ID válido."]);
    }

    return (int) $id;
}

function obtenerUnidadesCU($pdo, $id) {
    if ($id !== null) {
        $stmt = $pdo->prepare(
            "SELECT * FROM unidades WHERE id_unidad = :id"
        );
        $stmt->execute([":id" => $id]);
        $resultado = $stmt->fetch();

        if (!$resultado) {
            responderCU(404, ["error" => "Unidad no encontrada."]);
        }

        responderCU(200, $resultado);
    }

    $stmt = $pdo->query(
        "SELECT * FROM unidades ORDER BY id_unidad"
    );
    responderCU(200, $stmt->fetchAll());
}

function guardarUnidadCU($pdo, $id, $datos) {
    $placas = trim((string) ($datos["placas"] ?? ""));
    $tipo = trim((string) ($datos["tipo"] ?? ""));
    $capacidad = $datos["capacidad_kg"] ?? null;

    if (
        $placas === "" ||
        $tipo === "" ||
        !is_numeric($capacidad) ||
        !is_finite((float) $capacidad) ||
        (float) $capacidad <= 0
    ) {
        responderCU(400, [
            "error" => "Las placas, el tipo y una capacidad mayor que cero son obligatorios."
        ]);
    }

    $activa = isset($datos["activa"])
        ? (int) (bool) $datos["activa"]
        : 1;

    if ($id === null) {
        $stmt = $pdo->prepare(
            "INSERT INTO unidades
             (placas, tipo, capacidad_kg, activa)
             VALUES (:placas, :tipo, :capacidad, :activa)"
        );

        $stmt->execute([
            ":placas" => $placas,
            ":tipo" => $tipo,
            ":capacidad" => (float) $capacidad,
            ":activa" => $activa
        ]);

        $nuevoId = (int) $pdo->lastInsertId();
        registrarCambio($pdo, "unidades", $nuevoId);

        responderCU(201, [
            "mensaje" => "Unidad creada correctamente.",
            "id_unidad" => $nuevoId
        ]);
    }

    $id = validarIdCU($id);

    $stmt = $pdo->prepare(
        "SELECT id_unidad FROM unidades WHERE id_unidad = :id"
    );
    $stmt->execute([":id" => $id]);

    if (!$stmt->fetch()) {
        responderCU(404, ["error" => "Unidad no encontrada."]);
    }

    $antes = leerFilaHistorial($pdo, "unidades", $id);
    $stmt = $pdo->prepare(
        "UPDATE unidades
         SET placas = :placas, tipo = :tipo,
             capacidad_kg = :capacidad, activa = :activa
         WHERE id_unidad = :id"
    );

    $stmt->execute([
        ":placas" => $placas,
        ":tipo" => $tipo,
        ":capacidad" => (float) $capacidad,
        ":activa" => $activa,
        ":id" => $id
    ]);
    registrarCambio($pdo, "unidades", $id, $antes);

    responderCU(200, ["mensaje" => "Unidad actualizada correctamente."]);
}

function eliminarUnidadCU($pdo, $id) {
    $id = validarIdCU($id);

    $stmt = $pdo->prepare(
        "SELECT id_unidad FROM unidades WHERE id_unidad = :id"
    );
    $stmt->execute([":id" => $id]);

    if (!$stmt->fetch()) {
        responderCU(404, ["error" => "Unidad no encontrada."]);
    }

    $stmt = $pdo->prepare(
        "SELECT 1 FROM fletes WHERE id_unidad = :id LIMIT 1"
    );
    $stmt->execute([":id" => $id]);

    if ($stmt->fetch()) {
        $antes = leerFilaHistorial($pdo, "unidades", $id);
        $stmt = $pdo->prepare(
            "UPDATE unidades SET activa = 0 WHERE id_unidad = :id"
        );
        $stmt->execute([":id" => $id]);
        registrarCambio($pdo, "unidades", $id, $antes);

        responderCU(200, ["mensaje" => "Unidad desactivada porque tiene fletes asociados."]);
    }

    $antes = leerFilaHistorial($pdo, "unidades", $id);
    $stmt = $pdo->prepare(
        "DELETE FROM unidades WHERE id_unidad = :id"
    );
    $stmt->execute([":id" => $id]);
    registrarCambio($pdo, "unidades", $id, $antes);

    responderCU(200, ["mensaje" => "Unidad eliminada correctamente."]);
}

function obtenerChoferesCU($pdo, $id) {
    if ($id !== null) {
        $stmt = $pdo->prepare(
            "SELECT * FROM choferes WHERE id_chofer = :id"
        );
        $stmt->execute([":id" => $id]);
        $resultado = $stmt->fetch();

        if (!$resultado) {
            responderCU(404, ["error" => "Chofer no encontrado."]);
        }

        responderCU(200, $resultado);
    }

    $stmt = $pdo->query(
        "SELECT * FROM choferes ORDER BY id_chofer"
    );
    responderCU(200, $stmt->fetchAll());
}

function guardarChoferCU($pdo, $id, $datos) {
    $nombre = trim((string) ($datos["nombre"] ?? ""));
    $telefono = trim((string) ($datos["telefono"] ?? ""));
    $licencia = trim((string) ($datos["num_licencia"] ?? ""));

    if ($nombre === "" || $licencia === "") {
        responderCU(400, [
            "error" => "El nombre y el número de licencia son obligatorios."
        ]);
    }

    if ($id === null) {
        $stmt = $pdo->prepare(
            "INSERT INTO choferes (nombre, telefono, num_licencia)
             VALUES (:nombre, :telefono, :licencia)"
        );

        $stmt->execute([
            ":nombre" => $nombre,
            ":telefono" => $telefono,
            ":licencia" => $licencia
        ]);

        $nuevoId = (int) $pdo->lastInsertId();
        registrarCambio($pdo, "choferes", $nuevoId);

        responderCU(201, [
            "mensaje" => "Chofer creado correctamente.",
            "id_chofer" => $nuevoId
        ]);
    }

    $id = validarIdCU($id);

    $stmt = $pdo->prepare(
        "SELECT id_chofer FROM choferes WHERE id_chofer = :id"
    );
    $stmt->execute([":id" => $id]);

    if (!$stmt->fetch()) {
        responderCU(404, ["error" => "Chofer no encontrado."]);
    }

    $activo = isset($datos["activo"])
        ? (int) (bool) $datos["activo"]
        : 1;

    $antes = leerFilaHistorial($pdo, "choferes", $id);
    $stmt = $pdo->prepare(
        "UPDATE choferes
         SET nombre = :nombre, telefono = :telefono,
             num_licencia = :licencia, activo = :activo
         WHERE id_chofer = :id"
    );

    $stmt->execute([
        ":nombre" => $nombre,
        ":telefono" => $telefono,
        ":licencia" => $licencia,
        ":activo" => $activo,
        ":id" => $id
    ]);
    registrarCambio($pdo, "choferes", $id, $antes);

    responderCU(200, ["mensaje" => "Chofer actualizado correctamente."]);
}

function eliminarChoferCU($pdo, $id) {
    $id = validarIdCU($id);

    $stmt = $pdo->prepare(
        "SELECT id_chofer FROM choferes WHERE id_chofer = :id"
    );
    $stmt->execute([":id" => $id]);

    if (!$stmt->fetch()) {
        responderCU(404, ["error" => "Chofer no encontrado."]);
    }

    $stmt = $pdo->prepare(
        "SELECT 1 FROM fletes WHERE id_chofer = :id LIMIT 1"
    );
    $stmt->execute([":id" => $id]);

    if ($stmt->fetch()) {
        $antes = leerFilaHistorial($pdo, "choferes", $id);
        $stmt = $pdo->prepare(
            "UPDATE choferes SET activo = 0 WHERE id_chofer = :id"
        );
        $stmt->execute([":id" => $id]);
        registrarCambio($pdo, "choferes", $id, $antes);

        responderCU(200, ["mensaje" => "Chofer desactivado porque tiene fletes asociados."]);
    }

    $antes = leerFilaHistorial($pdo, "choferes", $id);
    $stmt = $pdo->prepare(
        "DELETE FROM choferes WHERE id_chofer = :id"
    );
    $stmt->execute([":id" => $id]);
    registrarCambio($pdo, "choferes", $id, $antes);

    responderCU(200, ["mensaje" => "Chofer eliminado correctamente."]);
}

$pdo = conectarCU();
$metodo = $_SERVER["REQUEST_METHOD"];
$recurso = $_GET["recurso"] ?? "";
$id = $_GET["id"] ?? null;

try {
    switch ($recurso) {
        case "unidades":
            switch ($metodo) {
                case "GET":
                    obtenerUnidadesCU($pdo, $id);
                    break;
                case "POST":
                    guardarUnidadCU($pdo, null, leerEntradaCU());
                    break;
                case "PUT":
                    guardarUnidadCU($pdo, $id, leerEntradaCU());
                    break;
                case "DELETE":
                    eliminarUnidadCU($pdo, $id);
                    break;
                default:
                    responderCU(405, ["error" => "Método no permitido."]);
            }
            break;

        case "choferes":
            switch ($metodo) {
                case "GET":
                    obtenerChoferesCU($pdo, $id);
                    break;
                case "POST":
                    guardarChoferCU($pdo, null, leerEntradaCU());
                    break;
                case "PUT":
                    guardarChoferCU($pdo, $id, leerEntradaCU());
                    break;
                case "DELETE":
                    eliminarChoferCU($pdo, $id);
                    break;
                default:
                    responderCU(405, ["error" => "Método no permitido."]);
            }
            break;

        default:
            responderCU(400, ["error" => "Recurso no válido."]);
    }
} catch (PDOException $e) {
    responderCU(500, [
        "error" => "Error al procesar la operación en la base de datos."
    ]);
}
