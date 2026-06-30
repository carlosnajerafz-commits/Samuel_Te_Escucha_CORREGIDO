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

$fecha         = trim($_POST["fecha"] ?? "");
$horaInicio    = trim($_POST["hora_inicio"] ?? "");
$horaFin       = trim($_POST["hora_fin"] ?? "");
$lugar         = trim($_POST["lugar"] ?? "");
$descripcion   = trim($_POST["descripcion"] ?? "");
$cupo          = (int)($_POST["cupo_maximo"] ?? 0);
$tipoComedor   = trim($_POST["tipo_comedor"] ?? "");
$grupoDirigido = trim($_POST["grupo_dirigido"] ?? "");
$menu          = trim($_POST["menu"] ?? "");

if (empty($fecha)) {
    header("Location: admin_comedor.php?error=fecha");
    exit;
}

if (!empty($horaInicio) && !empty($horaFin) && $horaInicio >= $horaFin) {
    header("Location: admin_comedor.php?error=hora");
    exit;
}

if ($cupo < 0) {
    header("Location: admin_comedor.php?error=cupo");
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO eventos_comedor (fecha, hora_inicio, hora_fin, lugar, descripcion, cupo_maximo, tipo_comedor, grupo_dirigido, menu, activo)
    VALUES (:fecha, :hora_inicio, :hora_fin, :lugar, :descripcion, :cupo, :tipo_comedor, :grupo_dirigido, :menu, TRUE)
");

$stmt->execute([
    ":fecha"          => $fecha,
    ":hora_inicio"    => $horaInicio ?: "00:00",
    ":hora_fin"       => $horaFin ?: "23:59",
    ":lugar"          => $lugar,
    ":descripcion"    => $descripcion,
    ":cupo"           => $cupo,
    ":tipo_comedor"   => $tipoComedor,
    ":grupo_dirigido" => $grupoDirigido,
    ":menu"           => $menu,
]);

header("Location: admin_comedor.php?ok=1");
exit;
