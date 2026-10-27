
<?php

header("Content-Type: application/json; charset=UTF-8");

function responderFletes($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function conectarFletes() {
    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON");

        return $pdo;
    } catch (PDOException $e) {
        responderFletes(500, [
            "error" => "No se pudo conectar con la base de datos."
        ]);
    }
}

function leerJsonFletes() {
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) {
        responderFletes(400, ["error" => "El JSON enviado no es válido."]);
    }

    return $datos;
}

function validarIdFlete($id) {
    if (
        $id === null ||
        filter_var($id, FILTER_VALIDATE_INT) === false ||
        (int) $id < 1
    ) {
        responderFletes(400, ["error" => "Se requiere un ID válido."]);
    }

    return (int) $id;
}

function consultarFletes($pdo, $id = null, $soloPendientes = false) {
    $sql = "
        SELECT
            f.id_flete,
            f.id_cliente,
            c.nombre AS cliente,
            f.id_chofer,
            ch.nombre AS chofer,
            f.id_unidad,
            u.placas,
            u.tipo AS tipo_unidad,
            f.origen,
            f.destino,
            f.descripcion_carga,
            f.fecha_salida,
            f.fecha_entrega,
            f.monto,
            f.estatus,
            f.pagado
        FROM fletes f
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        INNER JOIN choferes ch ON ch.id_chofer = f.id_chofer
        INNER JOIN unidades u ON u.id_unidad = f.id_unidad
    ";

    $condiciones = [];
    $parametros = [];

    if ($id !== null) {
        $condiciones[] = "f.id_flete = :id";
        $parametros[":id"] = $id;
    }

    if ($soloPendientes) {
        $condiciones[] = "f.pagado = 0";
    }

    if ($condiciones) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }

    $sql .= " ORDER BY f.id_flete DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $resultados = $stmt->fetchAll();

    foreach ($resultados as &$flete) {
        $flete["id_flete"] = (int) $flete["id_flete"];
        $flete["id_cliente"] = (int) $flete["id_cliente"];
        $flete["id_chofer"] = (int) $flete["id_chofer"];
        $flete["id_unidad"] = (int) $flete["id_unidad"];
        $flete["monto"] = (float) $flete["monto"];
        $flete["pagado"] = (int) $flete["pagado"];
    }
    unset($flete);

    if ($id !== null) {
        if (!$resultados) {
            responderFletes(404, ["error" => "Flete no encontrado."]);
        }

        responderFletes(200, $resultados[0]);
    }

    responderFletes(200, $resultados);
}

function validarDatosFlete($pdo, $datos) {
    $idCliente = filter_var(
        $datos["id_cliente"] ?? null,
        FILTER_VALIDATE_INT
    );

    $idChofer = filter_var(
        $datos["id_chofer"] ?? null,
        FILTER_VALIDATE_INT
    );

    $idUnidad = filter_var(
        $datos["id_unidad"] ?? null,
        FILTER_VALIDATE_INT
    );

    $origen = trim((string) ($datos["origen"] ?? ""));
    $destino = trim((string) ($datos["destino"] ?? ""));
    $descripcion = trim((string) ($datos["descripcion_carga"] ?? ""));
    $fechaSalida = trim((string) ($datos["fecha_salida"] ?? ""));
    $fechaEntrega = trim((string) ($datos["fecha_entrega"] ?? ""));
    $monto = $datos["monto"] ?? null;
    $estatus = $datos["estatus"] ?? "pendiente";

    if (
        $idCliente === false || $idCliente === null || $idCliente < 1 ||
        $idChofer === false || $idChofer === null || $idChofer < 1 ||
        $idUnidad === false || $idUnidad === null || $idUnidad < 1
    ) {
        responderFletes(400, [
            "error" => "Selecciona un cliente, chofer y unidad válidos."
        ]);
    }

    if ($origen === "" || $destino === "") {
        responderFletes(400, [
            "error" => "El origen y el destino son obligatorios."
        ]);
    }

    if (
        !is_numeric($monto) ||
        !is_finite((float) $monto) ||
        (float) $monto <= 0
    ) {
        responderFletes(400, [
            "error" => "El monto debe ser mayor que cero."
        ]);
    }

    $estatusPermitidos = [
        "pendiente",
        "en_transito",
        "finalizado"
    ];

    if (!in_array($estatus, $estatusPermitidos, true)) {
        responderFletes(400, ["error" => "El estatus no es válido."]);
    }

    foreach ([
        "fecha_salida" => $fechaSalida,
        "fecha_entrega" => $fechaEntrega
    ] as $campo => $fecha) {
        if ($fecha !== "") {
            $fechaObjeto = DateTimeImmutable::createFromFormat("!Y-m-d", $fecha);

            if (
                !$fechaObjeto ||
                $fechaObjeto->format("Y-m-d") !== $fecha
            ) {
                responderFletes(400, [
                    "error" => "La fecha de $campo no es válida."
                ]);
            }
        }
    }

    if (
        $fechaSalida !== "" &&
        $fechaEntrega !== "" &&
        $fechaEntrega < $fechaSalida
    ) {
        responderFletes(400, [
            "error" => "La fecha de entrega no puede ser anterior a la salida."
        ]);
    }

    foreach ([
        "clientes" => ["id_cliente", $idCliente],
        "choferes" => ["id_chofer", $idChofer],
        "unidades" => ["id_unidad", $idUnidad]
    ] as $tabla => [$columna, $valor]) {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM $tabla WHERE $columna = :id"
        );
        $stmt->execute([":id" => $valor]);

        if (!$stmt->fetch()) {
            responderFletes(400, [
                "error" => "Uno de los clientes, choferes o unidades seleccionados no existe."
            ]);
        }
    }

    return [
        "id_cliente" => $idCliente,
        "id_chofer" => $idChofer,
        "id_unidad" => $idUnidad,
        "origen" => $origen,
        "destino" => $destino,
        "descripcion_carga" => $descripcion,
        "fecha_salida" => $fechaSalida !== "" ? $fechaSalida : null,
        "fecha_entrega" => $fechaEntrega !== "" ? $fechaEntrega : null,
        "monto" => (float) $monto,
        "estatus" => $estatus
    ];
}

function guardarFlete($pdo, $id, $datos) {
    $flete = validarDatosFlete($pdo, $datos);

    if ($id === null) {
        $stmt = $pdo->prepare("
            INSERT INTO fletes (
                id_cliente, id_chofer, id_unidad,
                origen, destino, descripcion_carga,
                fecha_salida, fecha_entrega, monto, estatus, pagado
            ) VALUES (
                :id_cliente, :id_chofer, :id_unidad,
                :origen, :destino, :descripcion_carga,
                :fecha_salida, :fecha_entrega, :monto, :estatus, 0
            )
        ");

        $stmt->execute($flete);

        responderFletes(201, [
            "mensaje" => "Flete registrado correctamente.",
            "id_flete" => (int) $pdo->lastInsertId()
        ]);
    }

    $id = validarIdFlete($id);

    $stmt = $pdo->prepare(
        "SELECT pagado FROM fletes WHERE id_flete = :id"
    );
    $stmt->execute([":id" => $id]);
    $existente = $stmt->fetch();

    if (!$existente) {
        responderFletes(404, ["error" => "Flete no encontrado."]);
    }

    $stmt = $pdo->prepare("
        UPDATE fletes SET
            id_cliente = :id_cliente,
            id_chofer = :id_chofer,
            id_unidad = :id_unidad,
            origen = :origen,
            destino = :destino,
            descripcion_carga = :descripcion_carga,
            fecha_salida = :fecha_salida,
            fecha_entrega = :fecha_entrega,
            monto = :monto,
            estatus = :estatus
        WHERE id_flete = :id
    ");

    $flete["id"] = $id;
    $stmt->execute($flete);

    responderFletes(200, ["mensaje" => "Flete actualizado correctamente."]);
}

function marcarFletePagado($pdo, $id) {
    $id = validarIdFlete($id);

    $stmt = $pdo->prepare(
        "SELECT pagado FROM fletes WHERE id_flete = :id"
    );
    $stmt->execute([":id" => $id]);
    $flete = $stmt->fetch();

    if (!$flete) {
        responderFletes(404, ["error" => "Flete no encontrado."]);
    }

    if ((int) $flete["pagado"] === 1) {
        responderFletes(200, [
            "mensaje" => "El flete ya estaba pagado."
        ]);
    }

    $stmt = $pdo->prepare(
        "UPDATE fletes SET pagado = 1 WHERE id_flete = :id"
    );
    $stmt->execute([":id" => $id]);

    responderFletes(200, [
        "mensaje" => "Pago registrado correctamente.",
        "id_flete" => $id,
        "pagado" => 1
    ]);
}

$pdo = conectarFletes();
$metodo = $_SERVER["REQUEST_METHOD"];
$recurso = $_GET["recurso"] ?? "";
$id = $_GET["id"] ?? null;

try {
    if ($metodo === "GET") {
        consultarFletes(
            $pdo,
            $id !== null ? validarIdFlete($id) : null,
            $recurso === "fletes_por_pagar"
        );
    }

    if ($metodo === "POST" && $recurso === "fletes") {
        guardarFlete($pdo, null, leerJsonFletes());
    }

    if ($metodo === "PUT" && $recurso === "fletes") {
        guardarFlete($pdo, $id, leerJsonFletes());
    }

    if ($metodo === "PUT" && $recurso === "fletes_por_pagar") {
        marcarFletePagado($pdo, $id);
    }

    header("Allow: GET, POST, PUT");
    responderFletes(405, ["error" => "Método no permitido para este recurso."]);

} catch (PDOException $e) {
    responderFletes(500, [
        "error" => "No se pudo completar la operación del flete."
    ]);
}
