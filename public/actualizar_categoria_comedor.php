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

$id        = (int)($_POST["id"] ?? 0);
$categoria = trim($_POST["categoria_vulnerable"] ?? "");

$categorias_permitidas = [
    "Adultos mayores",
    "Personas con discapacidad",
    "Madres solteras y jefas de familia",
    "Niñas, niños y adolescentes",
    "Personas en situación de pobreza",
    "Personas desempleadas",
    "Personas en situación de calle",
    "Personas migrantes",
    "Personas cuidadoras sin ingresos suficientes",
    "Familias en situación de vulnerabilidad económica",
    "Personas con enfermedades que les impidan trabajar",
    "Víctimas de violencia o desplazamiento",
    "Comunidades indígenas en situación de marginación",
];

if ($id <= 0 || ($categoria !== "" && !in_array($categoria, $categorias_permitidas))) {
    header("Location: empleados_comedor.php");
    exit;
}

$stmt = $pdo->prepare("UPDATE registros_comedor SET categoria_vulnerable = :cat WHERE id = :id");
$stmt->execute([":cat" => $categoria, ":id" => $id]);

$redirect = $_POST["redirect"] ?? "";
$safe = parse_url($redirect, PHP_URL_QUERY);
header("Location: empleados_comedor.php?" . ($safe ? $safe . "&" : "") . "actualizado=1");
exit;
