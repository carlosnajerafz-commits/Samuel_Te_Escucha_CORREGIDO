<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: empleados_cita.php");
    exit;
}

$fecha        = trim($_POST["fecha_bloqueo"] ?? "");
$tipo         = trim($_POST["tipo_bloqueo"] ?? "");
$motivo       = trim($_POST["motivo_bloqueo"] ?? "");
$horas        = $_POST["horas_bloqueo"] ?? [];
$empleado_id  = (int) $_SESSION["empleado_id"];

/* =========================================
   VALIDACIONES
========================================= */
if ($fecha === "") {
    header("Location: empleados_cita.php?bloqueo_error=fecha");
    exit;
}

// Verificar que sea un martes
$diaSemana = date("N", strtotime($fecha));
if ($diaSemana != 2) {
    header("Location: empleados_cita.php?bloqueo_error=no_martes");
    exit;
}

// Verificar que la fecha no sea pasada
if (strtotime($fecha) < strtotime(date("Y-m-d"))) {
    header("Location: empleados_cita.php?bloqueo_error=pasada");
    exit;
}

if ($tipo !== "dia_completo" && $tipo !== "horas") {
    header("Location: empleados_cita.php?bloqueo_error=tipo");
    exit;
}

if ($tipo === "horas" && empty($horas)) {
    header("Location: empleados_cita.php?bloqueo_error=sin_horas");
    exit;
}

/* =========================================
   GUARDAR BLOQUEO(S)
========================================= */
try {
    $pdo->beginTransaction();

    if ($tipo === "dia_completo") {
        // Eliminar bloqueos individuales previos de ese día (se reemplaza por completo)
        $stmtDel = $pdo->prepare("DELETE FROM bloqueos_cita WHERE fecha = :fecha");
        $stmtDel->execute([":fecha" => $fecha]);

        $stmt = $pdo->prepare("
            INSERT INTO bloqueos_cita (fecha, hora, dia_completo, motivo, empleado_id)
            VALUES (:fecha, NULL, TRUE, :motivo, :empleado_id)
        ");
        $stmt->execute([
            ":fecha"       => $fecha,
            ":motivo"      => $motivo,
            ":empleado_id" => $empleado_id,
        ]);
    } else {
        // Bloquear horas individuales
        $stmt = $pdo->prepare("
            INSERT INTO bloqueos_cita (fecha, hora, dia_completo, motivo, empleado_id)
            VALUES (:fecha, :hora, FALSE, :motivo, :empleado_id)
        ");

        foreach ($horas as $hora) {
            $hora = trim($hora);
            if (!preg_match('/^\d{2}:\d{2}$/', $hora)) continue;

            // Verificar si ya existe ese bloqueo
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*) FROM bloqueos_cita
                WHERE fecha = :fecha AND hora = :hora
            ");
            $stmtCheck->execute([":fecha" => $fecha, ":hora" => $hora . ":00"]);

            if ((int)$stmtCheck->fetchColumn() === 0) {
                $stmt->execute([
                    ":fecha"       => $fecha,
                    ":hora"        => $hora . ":00",
                    ":motivo"      => $motivo,
                    ":empleado_id" => $empleado_id,
                ]);
            }
        }
    }

    $pdo->commit();
    header("Location: empleados_cita.php?bloqueo_ok=1");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: empleados_cita.php?bloqueo_error=general");
    exit;
}
