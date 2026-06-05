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

$flagFile = __DIR__ . "/maintenance.flag";

if (file_exists($flagFile)) {
    // Desactivar mantenimiento
    unlink($flagFile);
    header("Location: dashboard.php?mantenimiento=desactivado");
} else {
    // Activar mantenimiento
    file_put_contents($flagFile, date("Y-m-d H:i:s"));
    header("Location: dashboard.php?mantenimiento=activado");
}
exit;
