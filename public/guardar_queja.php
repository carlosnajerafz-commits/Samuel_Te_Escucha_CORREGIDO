<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: queja.php?error=1");
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
$codigo_postal = trim($_POST["codigo_postal"] ?? "");
$colonia = trim($_POST["colonia"] ?? "");
$municipio = trim($_POST["municipio"] ?? "");
$tipo = trim($_POST["tipo"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");

$descripcion = strip_tags($descripcion);
$descripcion = substr($descripcion, 0, 500);

if (
    $nombre === "" ||
    $apellido_paterno === "" ||
    $apellido_materno === "" ||
    $celular_1 === "" ||
    $correo === "" ||
    $calle === "" ||
    $no_exterior === "" ||
    $codigo_postal === "" ||
    $colonia === "" ||
    $municipio === "" ||
    $tipo === "" ||
    $descripcion === ""
) {
    header("Location: queja.php?error=1");
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $celular_1)) {
    header("Location: queja.php?error=1");
    exit;
}

if ($celular_2 !== "" && !preg_match('/^[0-9]{10}$/', $celular_2)) {
    header("Location: queja.php?error=1");
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: queja.php?error=1");
    exit;
}

$evidenciaPath = null;

if (isset($_FILES["evidencia"]) && $_FILES["evidencia"]["error"] === UPLOAD_ERR_OK) {
    $maxSize = 5 * 1024 * 1024;

    if ($_FILES["evidencia"]["size"] > $maxSize) {
        header("Location: queja.php?error=1");
        exit;
    }

    $ext = strtolower(pathinfo($_FILES["evidencia"]["name"], PATHINFO_EXTENSION));
    $permitidas = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($ext, $permitidas, true)) {
        header("Location: queja.php?error=1");
        exit;
    }

    $uploadDir = __DIR__ . "/uploads";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $nombreArchivo = uniqid("queja_", true) . "." . $ext;
    $destino = $uploadDir . "/" . $nombreArchivo;

    if (!move_uploaded_file($_FILES["evidencia"]["tmp_name"], $destino)) {
        header("Location: queja.php?error=1");
        exit;
    }

    $evidenciaPath = "uploads/" . $nombreArchivo;
} else {
    header("Location: queja.php?error=1");
    exit;
}

try {
    $sql = "INSERT INTO quejas (
                nombre,
                apellido_paterno,
                apellido_materno,
                celular_1,
                celular_2,
                correo,
                seccion_electoral,
                calle,
                no_exterior,
                no_interior,
                colonia,
                municipio,
                codigo_postal,
                tipo,
                descripcion,
                evidencia_path,
                estatus
            ) VALUES (
                :nombre,
                :apellido_paterno,
                :apellido_materno,
                :celular_1,
                :celular_2,
                :correo,
                :seccion_electoral,
                :calle,
                :no_exterior,
                :no_interior,
                :colonia,
                :municipio,
                :codigo_postal,
                :tipo,
                :descripcion,
                :evidencia_path,
                :estatus
            )";

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
        ":tipo" => $tipo,
        ":descripcion" => $descripcion,
        ":evidencia_path" => $evidenciaPath,
        ":estatus" => "pendiente"
    ]);

   $id = $pdo->lastInsertId();

header("Location: queja.php?ok=1&id=" . $id);
exit;

} catch (PDOException $e) {
    header("Location: queja.php?error=1");
    exit;
}
?>