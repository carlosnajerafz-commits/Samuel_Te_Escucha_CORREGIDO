<?php
require_once "db.php";
require_once "includes/rate_limit.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: comedor.php");
    exit;
}

rate_limit_or_redirect($pdo, 'comedor', 3, 3600, 'comedor.php?error=limite');

$eventoId      = (int)($_POST["evento_id"] ?? 0);
$nombre        = trim($_POST["nombre"] ?? "");
$apellidoPat   = trim($_POST["apellido_paterno"] ?? "");
$apellidoMat   = trim($_POST["apellido_materno"] ?? "");
$celular1      = preg_replace("/\D/", "", $_POST["celular_1"] ?? "");
$celular2      = preg_replace("/\D/", "", $_POST["celular_2"] ?? "");
$correo        = trim($_POST["correo"] ?? "");
$calle         = trim($_POST["calle"] ?? "");
$noExterior    = trim($_POST["no_exterior"] ?? "");
$noInterior    = trim($_POST["no_interior"] ?? "");
$colonia       = trim($_POST["colonia"] ?? "");
$municipio     = trim($_POST["municipio"] ?? "");
$cp            = preg_replace("/\D/", "", $_POST["codigo_postal"] ?? "");
$numPersonas   = (int)($_POST["numero_personas"] ?? 1);
$observaciones = trim($_POST["observaciones"] ?? "");

// Validaciones
if ($eventoId <= 0) {
    header("Location: comedor.php?error=evento");
    exit;
}

if (strlen($celular1) !== 10) {
    header("Location: comedor.php?error=celular&evento=$eventoId");
    exit;
}

if ($celular2 !== "" && strlen($celular2) !== 10) {
    header("Location: comedor.php?error=celular&evento=$eventoId");
    exit;
}

if ($numPersonas < 1 || $numPersonas > 20) {
    header("Location: comedor.php?error=personas&evento=$eventoId");
    exit;
}

// Verificar que el evento exista y esté activo
$stmtEvento = $pdo->prepare("
    SELECT id, cupo_maximo FROM eventos_comedor
    WHERE id = :id AND activo = TRUE AND fecha >= CURRENT_DATE
");
$stmtEvento->execute([":id" => $eventoId]);
$evento = $stmtEvento->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header("Location: comedor.php?error=evento");
    exit;
}

// Verificar cupo si aplica
if ((int)$evento["cupo_maximo"] > 0) {
    $stmtCupo = $pdo->prepare("
        SELECT COALESCE(SUM(numero_personas), 0) AS ocupado
        FROM registros_comedor
        WHERE evento_id = :id AND estatus != 'cancelado'
    ");
    $stmtCupo->execute([":id" => $eventoId]);
    $ocupado = (int)$stmtCupo->fetchColumn();
    if ($ocupado + $numPersonas > (int)$evento["cupo_maximo"]) {
        header("Location: comedor.php?error=cupo&evento=$eventoId");
        exit;
    }
}

$stmt = $pdo->prepare("
    INSERT INTO registros_comedor
        (evento_id, nombre, apellido_paterno, apellido_materno,
         celular_1, celular_2, correo,
         calle, no_exterior, no_interior,
         colonia, municipio, codigo_postal,
         numero_personas, observaciones, estatus)
    VALUES
        (:evento_id, :nombre, :apellido_paterno, :apellido_materno,
         :celular_1, :celular_2, :correo,
         :calle, :no_exterior, :no_interior,
         :colonia, :municipio, :codigo_postal,
         :numero_personas, :observaciones, 'pendiente')
");

$stmt->execute([
    ":evento_id"         => $eventoId,
    ":nombre"            => $nombre,
    ":apellido_paterno"  => $apellidoPat,
    ":apellido_materno"  => $apellidoMat,
    ":celular_1"         => $celular1,
    ":celular_2"         => $celular2,
    ":correo"            => $correo,
    ":calle"             => $calle,
    ":no_exterior"       => $noExterior,
    ":no_interior"       => $noInterior,
    ":colonia"           => $colonia,
    ":municipio"         => $municipio,
    ":codigo_postal"     => $cp,
    ":numero_personas"   => $numPersonas,
    ":observaciones"     => $observaciones,
]);

$id = $pdo->lastInsertId();
header("Location: comedor.php?ok=1&id=$id");
exit;
