<?php
require_once "includes/session.php";
require_once "db.php";
require_once "includes/csrf.php";
require_once "includes/helpers.php";
csrf_validate('empleados_comedor.php');

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: empleados_comedor.php");
    exit;
}

try {
    $pdo->exec("ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS categoria_vulnerable VARCHAR(150) DEFAULT ''");
} catch (PDOException $e) { /* ignorar */ }

$id        = (int)($_POST["id"] ?? 0);
$categoria = trim($_POST["categoria_vulnerable"] ?? "");

if ($id <= 0 || ($categoria !== "" && !in_array($categoria, categoriasVulnerables(), true))) {
    header("Location: empleados_comedor.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE registros_comedor SET categoria_vulnerable = :cat WHERE id = :id");
$stmt->execute([":cat" => $categoria, ":id" => $id]);

$redirect = $_POST["redirect"] ?? "";
$safe = parse_url($redirect, PHP_URL_QUERY);
header("Location: empleados_comedor.php?" . ($safe ? $safe . "&" : "") . "actualizado=1");
exit;
