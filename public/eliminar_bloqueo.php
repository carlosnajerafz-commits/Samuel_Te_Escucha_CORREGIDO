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

$id = (int) ($_POST["bloqueo_id"] ?? 0);

if ($id <= 0) {
    header("Location: empleados_cita.php?bloqueo_error=id");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM bloqueos_cita WHERE id = :id");
    $stmt->execute([":id" => $id]);

    header("Location: empleados_cita.php?bloqueo_eliminado=1");
    exit;

} catch (PDOException $e) {
    header("Location: empleados_cita.php?bloqueo_error=general");
    exit;
}
