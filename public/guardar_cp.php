<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_cp.php");
    exit;
}

$accion = trim($_POST["accion"] ?? "");

/* =========================================
   AGREGAR COLONIA A UN CP
========================================= */
if ($accion === "agregar") {
    $cp        = trim($_POST["codigo_postal"] ?? "");
    $colonia   = trim($_POST["colonia"]       ?? "");
    $municipio = trim($_POST["municipio"]     ?? "");

    if (!preg_match('/^[0-9]{5}$/', $cp) || $colonia === "" || $municipio === "") {
        header("Location: admin_cp.php?error=campos");
        exit;
    }

    // Evitar duplicados exactos
    $check = $pdo->prepare("
        SELECT id FROM codigos_postales
        WHERE codigo_postal = :cp AND colonia = :colonia
    ");
    $check->execute([":cp" => $cp, ":colonia" => $colonia]);

    if ($check->fetch()) {
        header("Location: admin_cp.php?error=duplicado&cp=" . urlencode($cp));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO codigos_postales (codigo_postal, colonia, municipio)
        VALUES (:cp, :colonia, :municipio)
    ");
    $stmt->execute([
        ":cp"        => $cp,
        ":colonia"   => $colonia,
        ":municipio" => $municipio,
    ]);

    header("Location: admin_cp.php?ok=agregado&cp=" . urlencode($cp));
    exit;
}

/* =========================================
   ELIMINAR UNA COLONIA
========================================= */
if ($accion === "eliminar") {
    $id = (int)($_POST["id"] ?? 0);

    if ($id <= 0) {
        header("Location: admin_cp.php?error=id");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM codigos_postales WHERE id = :id");
    $stmt->execute([":id" => $id]);

    $cp = trim($_POST["cp_retorno"] ?? "");
    header("Location: admin_cp.php?ok=eliminado" . ($cp !== "" ? "&cp=" . urlencode($cp) : ""));
    exit;
}

header("Location: admin_cp.php");
exit;
