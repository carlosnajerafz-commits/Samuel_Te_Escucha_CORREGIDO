<?php
require_once "db.php";
header("Content-Type: application/json");

$fecha = $_GET["fecha"] ?? "";

if ($fecha === "") {
    echo json_encode([]);
    exit;
}

/* =========================================
   1. Horas ocupadas por citas existentes
========================================= */
$sql = "
    SELECT hora
    FROM citas
    WHERE fecha = :fecha
      AND estatus IN ('solicitada', 'aceptada')
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":fecha" => $fecha]);

$horas = $stmt->fetchAll(PDO::FETCH_COLUMN);

$horasOcupadas = array_map(function ($hora) {
    return substr((string)$hora, 0, 5);
}, $horas);

/* =========================================
   2. Horas bloqueadas por empleados
========================================= */
$sqlBloqueos = "
    SELECT hora
    FROM bloqueos_cita
    WHERE fecha = :fecha
      AND dia_completo = FALSE
      AND hora IS NOT NULL
";

$stmtBloqueos = $pdo->prepare($sqlBloqueos);
$stmtBloqueos->execute([":fecha" => $fecha]);

$horasBloqueadas = $stmtBloqueos->fetchAll(PDO::FETCH_COLUMN);

$horasBloqueadasNorm = array_map(function ($hora) {
    return substr((string)$hora, 0, 5);
}, $horasBloqueadas);

/* =========================================
   3. Si el día completo está bloqueado,
      retornar todas las horas posibles
========================================= */
$sqlDiaCompleto = "
    SELECT COUNT(*)
    FROM bloqueos_cita
    WHERE fecha = :fecha
      AND dia_completo = TRUE
";

$stmtDia = $pdo->prepare($sqlDiaCompleto);
$stmtDia->execute([":fecha" => $fecha]);

if ((int)$stmtDia->fetchColumn() > 0) {
    // Día completamente bloqueado: retornar todos los slots
    $todosLosSlots = [
        "09:00","09:20","09:40","10:00","10:20","10:40",
        "11:00","11:20","11:40","12:00","12:20","12:40",
        "13:00","13:20","13:40","14:00","14:20","14:40"
    ];
    echo json_encode($todosLosSlots);
    exit;
}

/* =========================================
   4. Combinar horas ocupadas + bloqueadas
========================================= */
$resultado = array_values(array_unique(array_merge($horasOcupadas, $horasBloqueadasNorm)));

echo json_encode($resultado);