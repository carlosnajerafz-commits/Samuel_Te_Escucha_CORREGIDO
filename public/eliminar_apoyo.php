<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: empleados_apoyo.php");
    exit;
}

$id = (int)($_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: empleados_apoyo.php?error=apoyo");
    exit;
}

try {
    $sql = "UPDATE apoyos
            SET activo = FALSE
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: empleados_apoyo.php?apoyo_eliminado=1");
    exit;

} catch (PDOException $e) {
    die("Error al eliminar apoyo: " . $e->getMessage());
}
?>