<?php
require_once "db.php";
header("Content-Type: application/json");

$fecha = $_GET["fecha"] ?? "";

if ($fecha === "") {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT hora
    FROM citas
    WHERE fecha = :fecha
      AND estatus IN ('solicitada', 'aceptada')
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":fecha" => $fecha]);

$horas = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(array_map(function ($hora) {
    return substr((string)$hora, 0, 5);
}, $horas));