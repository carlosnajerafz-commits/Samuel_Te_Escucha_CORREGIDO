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

$id = (int)($_POST["id"] ?? 0);

if ($id > 0) {
    $pdo->prepare("DELETE FROM registros_comedor WHERE id = :id")->execute([":id" => $id]);
}

header("Location: empleados_comedor.php?eliminado=1");
exit;
