
<?php

header("Content-Type: application/json; charset=UTF-8");

function responderCliente($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function conectarClientes() {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON");
        return $pdo;
    } catch (PDOException $e) {
        responderCliente(500, ["error" => "No se pudo conectar con SQLite."]);
    }
}

function validarCliente($entrada) {
    if (!is_array($entrada)) {
        responderCliente(400, ["error" => "El JSON enviado no es válido."]);
    }

    $nombre = trim((string) ($entrada["nombre"] ?? ""));
    $telefono = trim((string) ($entrada["telefono"] ?? ""));
    $correo = trim((string) ($entrada["correo"] ?? ""));
    $direccion = trim((string) ($entrada["direccion"] ?? ""));

    if ($nombre === "") {
        responderCliente(400, ["error" => "El nombre es obligatorio."]);
    }

    if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderCliente(400, ["error" => "El correo electrónico no es válido."]);
    }

    return [
        "nombre" => $nombre,
        "telefono" => $telefono,
        "correo" => $correo,
        "direccion" => $direccion
    ];
}

$pdo = conectarClientes();
$metodo = $_SERVER["REQUEST_METHOD"];
$id = null;

if (isset($_GET["id"])) {
    $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        responderCliente(400, ["error" => "ID no válido."]);
    }
}

$entrada = null;

if (in_array($metodo, ["POST", "PUT"], true)) {
    $entrada = json_decode(file_get_contents("php://input"), true);

    if (!is_array($entrada)) {
        responderCliente(400, ["error" => "El JSON enviado no es válido."]);
    }
}

try {
    switch ($metodo) {
        case "GET":
            if ($id !== null) {
                $stmt = $pdo->prepare(
                    "SELECT id_cliente, nombre, telefono, correo, direccion, activo
                     FROM clientes WHERE id_cliente = :id"
                );
                $stmt->execute([":id" => $id]);
                $cliente = $stmt->fetch();

                if (!$cliente) {
                    responderCliente(404, ["error" => "Cliente no encontrado."]);
                }

                responderCliente(200, $cliente);
            }

            $stmt = $pdo->query(
                "SELECT id_cliente, nombre, telefono, correo, direccion, activo
                 FROM clientes ORDER BY id_cliente"
            );

            responderCliente(200, $stmt->fetchAll());

        case "POST":
            $datos = validarCliente($entrada);

            $stmt = $pdo->prepare(
                "INSERT INTO clientes
                 (nombre, telefono, correo, direccion, activo)
                 VALUES (:nombre, :telefono, :correo, :direccion, 1)"
            );

            $stmt->execute([
                ":nombre" => $datos["nombre"],
                ":telefono" => $datos["telefono"],
                ":correo" => $datos["correo"],
                ":direccion" => $datos["direccion"]
            ]);

            $nuevoId = (int) $pdo->lastInsertId();
            registrarCambio($pdo, "clientes", $nuevoId);

            responderCliente(201, [
                "mensaje" => "Cliente creado correctamente.",
                "id_cliente" => $nuevoId
            ]);

        case "PUT":
            if ($id === null) {
                responderCliente(400, ["error" => "Se requiere el ID del cliente."]);
            }

            $datos = validarCliente($entrada);

            $stmt = $pdo->prepare(
                "SELECT id_cliente FROM clientes WHERE id_cliente = :id"
            );
            $stmt->execute([":id" => $id]);

            if (!$stmt->fetch()) {
                responderCliente(404, ["error" => "Cliente no encontrado."]);
            }

            $activo = array_key_exists("activo", $entrada)
                ? (int) (bool) $entrada["activo"]
                : null;

            if ($activo === null) {
                $sql = "UPDATE clientes SET
                        nombre = :nombre, telefono = :telefono,
                        correo = :correo, direccion = :direccion
                        WHERE id_cliente = :id";
            } else {
                $sql = "UPDATE clientes SET
                        nombre = :nombre, telefono = :telefono,
                        correo = :correo, direccion = :direccion,
                        activo = :activo
                        WHERE id_cliente = :id";
            }

            $stmt = $pdo->prepare($sql);

            $parametros = [
                ":nombre" => $datos["nombre"],
                ":telefono" => $datos["telefono"],
                ":correo" => $datos["correo"],
                ":direccion" => $datos["direccion"],
                ":id" => $id
            ];

            if ($activo !== null) {
                $parametros[":activo"] = $activo;
            }

            $antes = leerFilaHistorial($pdo, "clientes", $id);
            $stmt->execute($parametros);
            registrarCambio($pdo, "clientes", $id, $antes);

            responderCliente(200, ["mensaje" => "Cliente actualizado correctamente."]);

        case "DELETE":
            if ($id === null) {
                responderCliente(400, ["error" => "Se requiere el ID del cliente."]);
            }

            $antes = leerFilaHistorial($pdo, "clientes", $id);
            $stmt = $pdo->prepare(
                "DELETE FROM clientes WHERE id_cliente = :id"
            );
            $stmt->execute([":id" => $id]);

            if ($stmt->rowCount() === 0) {
                responderCliente(404, ["error" => "Cliente no encontrado."]);
            }

            registrarCambio($pdo, "clientes", $id, $antes);
            responderCliente(200, ["mensaje" => "Cliente eliminado correctamente."]);

        default:
            header("Allow: GET, POST, PUT, DELETE, OPTIONS");
            responderCliente(405, ["error" => "Método no permitido."]);
    }
} catch (PDOException $e) {
    responderCliente(500, [
        "error" => "Error al procesar la operación de clientes."
    ]);
}
