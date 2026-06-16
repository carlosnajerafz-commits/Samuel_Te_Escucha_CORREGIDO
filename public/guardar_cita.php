<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cita.php");
    exit;
}

$nombre           = trim($_POST["nombre"]           ?? "");
$apellido_paterno = trim($_POST["apellido_paterno"] ?? "");
$apellido_materno = trim($_POST["apellido_materno"] ?? "");
$celular_1        = trim($_POST["celular_1"]        ?? "");
$celular_2        = trim($_POST["celular_2"]        ?? "");
$correo           = trim($_POST["correo"]           ?? "");
$calle            = trim($_POST["calle"]            ?? "");
$no_exterior      = trim($_POST["no_exterior"]      ?? "");
$no_interior      = trim($_POST["no_interior"]      ?? "");
$colonia          = trim($_POST["colonia"]          ?? "");
$municipio        = trim($_POST["municipio"]        ?? "");
$codigo_postal    = trim($_POST["codigo_postal"]    ?? "");
$fecha            = trim($_POST["fecha"]            ?? "");
$hora             = trim($_POST["hora"]             ?? "");
$motivo           = trim($_POST["motivo"]           ?? "");

/* =========================================
   VALIDACIONES
========================================= */
if (
    $nombre === "" || $apellido_paterno === "" || $apellido_materno === "" ||
    $celular_1 === "" || $celular_2 === "" || $correo === "" ||
    $calle === "" || $no_exterior === "" ||
    $colonia === "" || $municipio === "" || $codigo_postal === "" ||
    $fecha === "" || $hora === ""
) {
    header("Location: cita.php?error=campos");
    exit;
}

if (!preg_match('/^\d{10}$/', $celular_1) || !preg_match('/^\d{10}$/', $celular_2)) {
    header("Location: cita.php?error=celular");
    exit;
}

$diaSemana = date("N", strtotime($fecha));
if ($diaSemana != 2) {
    header("Location: cita.php?error=martes");
    exit;
}

/* Validar horario ocupado */
$stmtVerificar = $pdo->prepare("
    SELECT COUNT(*)
    FROM citas
    WHERE fecha = :fecha
      AND hora  = :hora
      AND estatus IN ('solicitada', 'aceptada')
");
$stmtVerificar->execute([":fecha" => $fecha, ":hora" => $hora]);

if ((int)$stmtVerificar->fetchColumn() > 0) {
    header("Location: cita.php?error=ocupada");
    exit;
}

/* Validar día bloqueado (día completo) */
$stmtBloqueoDia = $pdo->prepare("
    SELECT COUNT(*)
    FROM bloqueos_cita
    WHERE fecha = :fecha
      AND dia_completo = TRUE
");
$stmtBloqueoDia->execute([":fecha" => $fecha]);

if ((int)$stmtBloqueoDia->fetchColumn() > 0) {
    header("Location: cita.php?error=bloqueado");
    exit;
}

/* Validar hora bloqueada */
$stmtBloqueoHora = $pdo->prepare("
    SELECT COUNT(*)
    FROM bloqueos_cita
    WHERE fecha = :fecha
      AND hora = :hora
      AND dia_completo = FALSE
");
$stmtBloqueoHora->execute([":fecha" => $fecha, ":hora" => $hora]);

if ((int)$stmtBloqueoHora->fetchColumn() > 0) {
    header("Location: cita.php?error=bloqueado");
    exit;
}

/* =========================================
   GUARDAR CITA
========================================= */
try {
    $sql = "
        INSERT INTO citas (
            nombre, apellido_paterno, apellido_materno,
            celular_1, celular_2, correo,
            calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
            fecha, hora, motivo, ine_path, estatus, created_at
        ) VALUES (
            :nombre, :apellido_paterno, :apellido_materno,
            :celular_1, :celular_2, :correo,
            :calle, :no_exterior, :no_interior, :colonia, :municipio, :codigo_postal,
            :fecha, :hora, :motivo, NULL, 'solicitada', NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":nombre"           => $nombre,
        ":apellido_paterno" => $apellido_paterno,
        ":apellido_materno" => $apellido_materno,
        ":celular_1"        => $celular_1,
        ":celular_2"        => $celular_2,
        ":correo"           => $correo,
        ":calle"            => $calle,
        ":no_exterior"      => $no_exterior,
        ":no_interior"      => $no_interior,
        ":colonia"          => $colonia,
        ":municipio"        => $municipio,
        ":codigo_postal"    => $codigo_postal,
        ":fecha"            => $fecha,
        ":hora"             => $hora,
        ":motivo"           => $motivo,
    ]);

    $id = $pdo->lastInsertId();
    header("Location: cita.php?ok=1&id=" . $id);
    exit;

} catch (PDOException $e) {
    header("Location: cita.php?error=general");
    exit;
}
