
<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

$recurso = $_GET["recurso"] ?? "";

switch ($recurso) {
    case "clientes":
        require __DIR__ . "/api_Clientes.php";
        break;

    case "unidades":
    case "choferes":
        require __DIR__ . "/api_ChoferesUnidades.php";
        break;

    case "fletes":
    case "fletes_por_pagar":
        require __DIR__ . "/api_Fletes.php";
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "error" => "Recurso inválido o no especificado."
        ], JSON_UNESCAPED_UNICODE);
        exit;
}
