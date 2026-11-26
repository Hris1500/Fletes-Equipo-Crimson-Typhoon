<?php

const ARCHIVO_HISTORIAL = __DIR__ . "/historial.log.php";
const GUARDIA_HISTORIAL = "<?php exit; ?>\n";

function leerFilaHistorial($pdo, $entidad, $id) {
    $consultas = [
        "clientes" => "SELECT * FROM clientes WHERE id_cliente = ?",
        "choferes" => "SELECT * FROM choferes WHERE id_chofer = ?",
        "unidades" => "SELECT * FROM unidades WHERE id_unidad = ?",
        "fletes" => "
            SELECT f.id_flete, c.nombre AS cliente, ch.nombre AS chofer,
                   u.placas AS unidad, f.origen, f.destino,
                   f.descripcion_carga, f.fecha_salida, f.fecha_entrega,
                   f.monto, f.estatus, f.pagado
            FROM fletes f
            INNER JOIN clientes c ON c.id_cliente = f.id_cliente
            INNER JOIN choferes ch ON ch.id_chofer = f.id_chofer
            INNER JOIN unidades u ON u.id_unidad = f.id_unidad
            WHERE f.id_flete = ?
        "
    ];

    $stmt = $pdo->prepare($consultas[$entidad]);
    $stmt->execute([$id]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fila) {
        return null;
    }

    array_shift($fila);

    return $fila;
}

function registrarCambio($pdo, $entidad, $id, $antes = null) {
    $despues = leerFilaHistorial($pdo, $entidad, $id);
    $cambios = [];

    foreach (array_keys($despues ?? $antes ?? []) as $campo) {
        $valorAntes = $antes[$campo] ?? null;
        $valorDespues = $despues[$campo] ?? null;

        if ($valorAntes !== $valorDespues) {
            $cambios[$campo] = [$valorAntes, $valorDespues];
        }
    }

    if (!$cambios) {
        return;
    }

    $fila = $despues ?? $antes;

    $entrada = json_encode([
        "fecha" => date(DATE_ATOM),
        "accion" => $antes === null
            ? "creado"
            : ($despues === null ? "eliminado" : "modificado"),
        "entidad" => $entidad,
        "id" => (int) $id,
        "referencia" => $fila["nombre"]
            ?? $fila["placas"]
            ?? "{$fila["origen"]} - {$fila["destino"]}",
        "cambios" => $cambios
    ], JSON_UNESCAPED_UNICODE);

    $guardia = is_file(ARCHIVO_HISTORIAL) ? "" : GUARDIA_HISTORIAL;

    if (@file_put_contents(
        ARCHIVO_HISTORIAL,
        $guardia . $entrada . "\n",
        FILE_APPEND | LOCK_EX
    ) === false) {
        error_log("No se pudo escribir el historial de cambios.");
    }
}

function leerHistorial() {
    $lineas = is_file(ARCHIVO_HISTORIAL)
        ? file(ARCHIVO_HISTORIAL, FILE_IGNORE_NEW_LINES)
        : [];

    return array_reverse(array_values(array_filter(
        array_map(fn($linea) => json_decode($linea, true), $lineas)
    )));
}
