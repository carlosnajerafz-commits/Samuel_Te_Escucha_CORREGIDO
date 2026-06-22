<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

$filtroEvento = (int)($_GET["evento"] ?? 0);
$condicion = $filtroEvento > 0 ? "WHERE r.evento_id = :evento_id" : "";

$sql = "
    SELECT r.id, e.fecha, e.hora_inicio, e.lugar,
           r.nombre, r.apellido_paterno, r.apellido_materno,
           r.celular_1, r.celular_2, r.correo,
           r.seccion_electoral, r.calle, r.no_exterior, r.no_interior,
           r.colonia, r.municipio, r.codigo_postal,
           r.numero_personas, r.observaciones, r.estatus, r.created_at
    FROM registros_comedor r
    LEFT JOIN eventos_comedor e ON e.id = r.evento_id
    $condicion
    ORDER BY e.fecha ASC, r.created_at DESC
";

$stmt = $pdo->prepare($sql);
if ($filtroEvento > 0) {
    $stmt->execute([":evento_id" => $filtroEvento]);
} else {
    $stmt->execute();
}
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=comedor_solidario.csv");

$out = fopen("php://output", "w");
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

fputcsv($out, [
    "ID", "Fecha Evento", "Hora", "Lugar",
    "Nombre", "Apellido Paterno", "Apellido Materno",
    "Celular 1", "Celular 2", "Correo",
    "Sección Electoral", "Calle", "No. Ext", "No. Int",
    "Colonia", "Municipio", "CP",
    "Núm. Personas", "Observaciones", "Estatus", "Fecha Registro"
]);

foreach ($registros as $r) {
    fputcsv($out, [
        $r["id"],
        $r["fecha"] ?? "",
        $r["hora_inicio"] ? substr($r["hora_inicio"], 0, 5) : "",
        $r["lugar"] ?? "",
        $r["nombre"],
        $r["apellido_paterno"],
        $r["apellido_materno"],
        $r["celular_1"],
        $r["celular_2"],
        $r["correo"],
        $r["seccion_electoral"],
        $r["calle"],
        $r["no_exterior"],
        $r["no_interior"],
        $r["colonia"],
        $r["municipio"],
        $r["codigo_postal"],
        $r["numero_personas"],
        $r["observaciones"],
        $r["estatus"],
        $r["created_at"],
    ]);
}

fclose($out);
exit;
