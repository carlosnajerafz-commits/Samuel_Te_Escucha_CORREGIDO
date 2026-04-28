<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: empleados_queja.php");
    exit;
}

$id = (int)($_POST["id"] ?? 0);
$estatus = trim($_POST["estatus"] ?? "");

$permitidos = ["atendida", "cerrada", "completada"];

if ($id <= 0 || !in_array($estatus, $permitidos, true)) {
    header("Location: empleados_queja.php");
    exit;
}

try {
    $sql = "UPDATE quejas SET estatus = :estatus WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":estatus" => $estatus,
        ":id" => $id
    ]);

    header("Location: empleados_queja.php?ok=1");
    exit;
} catch (PDOException $e) {
    die("Error al actualizar estatus de queja: " . $e->getMessage());
}
?>