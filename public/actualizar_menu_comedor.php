<?php
require_once "includes/session.php";
require_once "db.php";
require_once "includes/csrf.php";
csrf_validate('admin_comedor.php');

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_comedor.php");
    exit;
}

try {
    $pdo->exec("ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS menu TEXT DEFAULT ''");
} catch (PDOException $e) { /* ignorar */ }

$id   = (int)($_POST["id"] ?? 0);
$menu = trim($_POST["menu"] ?? "");

if ($id <= 0) {
    header("Location: admin_comedor.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE eventos_comedor SET menu = :menu WHERE id = :id");
$stmt->execute([":menu" => $menu, ":id" => $id]);

header("Location: admin_comedor.php?menu_ok=1");
exit;
