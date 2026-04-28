<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$id = (int)($_POST["id"] ?? 0);
$estatus = trim($_POST["estatus"] ?? "");

$estatusPermitidos = ["aceptada", "rechazada", "realizada", "cancelada"];

if ($id <= 0 || !in_array($estatus, $estatusPermitidos, true)) {
    header("Location: dashboard.php");
    exit;
}

try {
    $sql = "UPDATE citas
            SET estatus = :estatus
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":estatus" => $estatus,
        ":id" => $id
    ]);

    header("Location: dashboard.php");
    exit;
} catch (PDOException $e) {
    die("Error al actualizar el estatus de la solicitud/cita: " . $e->getMessage());
}