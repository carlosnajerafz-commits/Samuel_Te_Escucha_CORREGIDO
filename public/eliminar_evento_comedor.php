<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_comedor.php");
    exit;
}

$id     = (int)($_POST["id"] ?? 0);
$accion = $_POST["accion"] ?? "";

if ($id <= 0) {
    header("Location: admin_comedor.php");
    exit;
}

if ($accion === "eliminar") {
    $pdo->prepare("DELETE FROM eventos_comedor WHERE id = :id")->execute([":id" => $id]);
    header("Location: admin_comedor.php?eliminado=1");
} elseif ($accion === "desactivar") {
    $pdo->prepare("UPDATE eventos_comedor SET activo = FALSE WHERE id = :id")->execute([":id" => $id]);
    header("Location: admin_comedor.php?desactivado=1");
} elseif ($accion === "activar") {
    $pdo->prepare("UPDATE eventos_comedor SET activo = TRUE WHERE id = :id")->execute([":id" => $id]);
    header("Location: admin_comedor.php?activado=1");
} else {
    header("Location: admin_comedor.php");
}
exit;
