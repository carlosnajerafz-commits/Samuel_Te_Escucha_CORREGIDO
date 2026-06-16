<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: apoyos.php?error=1");
    exit;
}

$apoyo            = trim($_POST["apoyo"]            ?? "");
$nombre           = trim($_POST["nombre"]           ?? "");
$apellido_paterno = trim($_POST["apellido_paterno"] ?? "");
$apellido_materno = trim($_POST["apellido_materno"] ?? "");
$celular_1        = trim($_POST["celular_1"]        ?? "");
$celular_2        = trim($_POST["celular_2"]        ?? "");
$correo           = trim($_POST["correo"]           ?? "");
$calle            = trim($_POST["calle"]            ?? "");
$no_exterior      = trim($_POST["no_exterior"]      ?? "");
$no_interior      = trim($_POST["no_interior"]      ?? "");
$codigo_postal    = trim($_POST["codigo_postal"]    ?? "");
$colonia          = trim($_POST["colonia"]          ?? "");
$municipio        = trim($_POST["municipio"]        ?? "");
$observaciones    = trim($_POST["observaciones"]    ?? "");

$observaciones = strip_tags($observaciones);
$observaciones = substr($observaciones, 0, 500);

/* =========================================
   VALIDACIONES
========================================= */
$faltantes = [];

if ($apoyo        === "") $faltantes[] = "apoyo";
if ($nombre       === "") $faltantes[] = "nombre";
if ($apellido_paterno === "") $faltantes[] = "apellido_paterno";
if ($apellido_materno === "") $faltantes[] = "apellido_materno";
if ($celular_1    === "") $faltantes[] = "celular_1";
if ($celular_2    === "") $faltantes[] = "celular_2";
if ($correo       === "") $faltantes[] = "correo";
if ($calle        === "") $faltantes[] = "calle";
if ($no_exterior  === "") $faltantes[] = "no_exterior";
if ($codigo_postal === "") $faltantes[] = "codigo_postal";
if ($colonia      === "") $faltantes[] = "colonia";
if ($municipio    === "") $faltantes[] = "municipio";

if (!empty($faltantes)) {
    die("FALTAN ESTOS CAMPOS: " . implode(", ", $faltantes));
}

if (!preg_match('/^[0-9]{10}$/', $celular_1) || !preg_match('/^[0-9]{10}$/', $celular_2)) {
    header("Location: apoyos.php?error=celular");
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("FALLO VALIDACION: correo no válido.");
}

if (!preg_match('/^[0-9]{5}$/', $codigo_postal)) {
    die("FALLO VALIDACION: código postal no válido.");
}

/* =========================================
   GUARDAR EN BD
========================================= */
try {
    $sql = "
        INSERT INTO registros_apoyos (
            apoyo, nombre, apellido_paterno, apellido_materno,
            celular_1, celular_2, correo,
            calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
            observaciones, ine_path, estatus
        ) VALUES (
            :apoyo, :nombre, :apellido_paterno, :apellido_materno,
            :celular_1, :celular_2, :correo,
            :calle, :no_exterior, :no_interior, :colonia, :municipio, :codigo_postal,
            :observaciones, NULL, 'pendiente'
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":apoyo"            => $apoyo,
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
        ":observaciones"    => $observaciones,
    ]);

    $id = $pdo->lastInsertId();
    header("Location: apoyos.php?ok=1&id=" . $id);
    exit;

} catch (PDOException $e) {
    die("Error SQL en guardar_apoyo.php: " . $e->getMessage());
}
