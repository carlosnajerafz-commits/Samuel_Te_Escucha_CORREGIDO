<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: empleados_comedor.php");
    exit;
}

$id     = (int)($_POST["id"] ?? 0);
$estatus = $_POST["estatus"] ?? "";
$permitidos = ["pendiente", "confirmado", "cancelado", "asistio"];

if ($id <= 0 || !in_array($estatus, $permitidos)) {
    header("Location: empleados_comedor.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE registros_comedor SET estatus = :estatus WHERE id = :id");
$stmt->execute([":estatus" => $estatus, ":id" => $id]);

header("Location: empleados_comedor.php?actualizado=1");
exit;
