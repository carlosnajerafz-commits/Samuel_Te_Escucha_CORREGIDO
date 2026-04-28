<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cita.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$apellido_paterno = trim($_POST["apellido_paterno"] ?? "");
$apellido_materno = trim($_POST["apellido_materno"] ?? "");
$celular_1 = trim($_POST["celular_1"] ?? "");
$celular_2 = trim($_POST["celular_2"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$seccion_electoral = trim($_POST["seccion_electoral"] ?? "");
$calle = trim($_POST["calle"] ?? "");
$no_exterior = trim($_POST["no_exterior"] ?? "");
$no_interior = trim($_POST["no_interior"] ?? "");
$colonia = trim($_POST["colonia"] ?? "");
$municipio = trim($_POST["municipio"] ?? "");
$codigo_postal = trim($_POST["codigo_postal"] ?? "");
$fecha = trim($_POST["fecha"] ?? "");
$hora = trim($_POST["hora"] ?? "");
$motivo = trim($_POST["motivo"] ?? "");

if (
    $nombre === "" || $apellido_paterno === "" || $apellido_materno === "" ||
    $celular_1 === "" || $celular_2 === "" || $correo === "" ||
    $seccion_electoral === "" || $calle === "" || $no_exterior === "" ||
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
$sqlVerificar = "
    SELECT COUNT(*) 
    FROM citas
    WHERE fecha = :fecha
      AND hora = :hora
      AND estatus IN ('solicitada', 'aceptada')
";
$stmtVerificar = $pdo->prepare($sqlVerificar);
$stmtVerificar->execute([
    ":fecha" => $fecha,
    ":hora" => $hora
]);

if ((int)$stmtVerificar->fetchColumn() > 0) {
    header("Location: cita.php?error=ocupada");
    exit;
}

/* Subir INE */
$inePath = null;

if (!isset($_FILES["ine_foto"]) || $_FILES["ine_foto"]["error"] !== UPLOAD_ERR_OK) {
    header("Location: cita.php?error=ine");
    exit;
}

$ext = strtolower(pathinfo($_FILES["ine_foto"]["name"], PATHINFO_EXTENSION));
$permitidas = ["jpg", "jpeg", "png", "webp"];

if (!in_array($ext, $permitidas, true)) {
    header("Location: cita.php?error=ine");
    exit;
}

if (!is_dir(__DIR__ . "/uploads")) {
    mkdir(__DIR__ . "/uploads", 0777, true);
}

$nombreArchivo = uniqid("ine_cita_", true) . "." . $ext;
$destino = __DIR__ . "/uploads/" . $nombreArchivo;

if (!move_uploaded_file($_FILES["ine_foto"]["tmp_name"], $destino)) {
    header("Location: cita.php?error=ine");
    exit;
}

$inePath = "uploads/" . $nombreArchivo;

/* Guardar cita */
try {
    $sql = "
        INSERT INTO citas (
            nombre, apellido_paterno, apellido_materno,
            celular_1, celular_2, correo, seccion_electoral,
            calle, no_exterior, no_interior, colonia, municipio, codigo_postal,
            fecha, hora, motivo, ine_path, estatus, created_at
        ) VALUES (
            :nombre, :apellido_paterno, :apellido_materno,
            :celular_1, :celular_2, :correo, :seccion_electoral,
            :calle, :no_exterior, :no_interior, :colonia, :municipio, :codigo_postal,
            :fecha, :hora, :motivo, :ine_path, 'solicitada', NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":nombre" => $nombre,
        ":apellido_paterno" => $apellido_paterno,
        ":apellido_materno" => $apellido_materno,
        ":celular_1" => $celular_1,
        ":celular_2" => $celular_2,
        ":correo" => $correo,
        ":seccion_electoral" => $seccion_electoral,
        ":calle" => $calle,
        ":no_exterior" => $no_exterior,
        ":no_interior" => $no_interior,
        ":colonia" => $colonia,
        ":municipio" => $municipio,
        ":codigo_postal" => $codigo_postal,
        ":fecha" => $fecha,
        ":hora" => $hora,
        ":motivo" => $motivo,
        ":ine_path" => $inePath
    ]);

    header("Location: cita.php?ok=1");
    exit;

} catch (PDOException $e) {
    header("Location: cita.php?error=general");
    exit;
}

$maxSize = 5 * 1024 * 1024; // 5 MB
$permitidas = ["jpg", "jpeg", "png", "webp"];

if (!isset($_FILES["ine_foto"]) || $_FILES["ine_foto"]["error"] !== UPLOAD_ERR_OK) {
    header("Location: cita.php?error=ine");
    exit;
}

if ($_FILES["ine_foto"]["size"] > $maxSize) {
    header("Location: cita.php?error=archivo_grande");
    exit;
}

$ext = strtolower(pathinfo($_FILES["ine_foto"]["name"], PATHINFO_EXTENSION));

if (!in_array($ext, $permitidas, true)) {
    header("Location: cita.php?error=ine");
    exit;
}
?>