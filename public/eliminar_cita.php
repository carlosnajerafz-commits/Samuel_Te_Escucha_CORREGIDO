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

if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

try {
    $sql = "DELETE FROM citas WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: dashboard.php?cita_eliminada=1");
    exit;
} catch (PDOException $e) {
    die("Error al eliminar la cita: " . $e->getMessage());
}