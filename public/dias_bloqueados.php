<?php
require_once "db.php";
header("Content-Type: application/json");

$mes  = (int) ($_GET["mes"]  ?? 0);
$anio = (int) ($_GET["anio"] ?? 0);

if ($mes < 1 || $mes > 12 || $anio < 2020) {
    echo json_encode([]);
    exit;
}

// Obtener días completamente bloqueados en el mes dado
$sql = "
    SELECT DISTINCT fecha
    FROM bloqueos_cita
    WHERE dia_completo = TRUE
      AND EXTRACT(MONTH FROM fecha) = :mes
      AND EXTRACT(YEAR FROM fecha)  = :anio
      AND fecha >= CURRENT_DATE
    ORDER BY fecha
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":mes" => $mes, ":anio" => $anio]);

$fechas = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(array_map(function ($f) {
    return (string) $f;
}, $fechas));
