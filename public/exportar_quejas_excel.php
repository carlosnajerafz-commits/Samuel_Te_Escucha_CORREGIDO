<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT 
        id,
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
        direccion,
        evidencia_path,
        created_at,
        estatus
    FROM quejas
    ORDER BY created_at DESC
";

$stmt = $pdo->query($sql);
$quejas = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=quejas.csv');

$output = fopen('php://output', 'w');

/* =========================
   ENCABEZADOS
========================= */
fputcsv($output, [
    'ID',
    'Nombre',
    'Apellido Paterno',
    'Apellido Materno',
    'Celular 1',
    'Celular 2',
    'Correo',
    'Sección Electoral',
    'Calle',
    'No Exterior',
    'No Interior',
    'Colonia',
    'Municipio',
    'Código Postal',
    'Tipo',
    'Dirección',
    'Evidencia',
    'Fecha Registro',
    'Estatus'
]);

/* =========================
   DATOS
========================= */
foreach ($quejas as $q) {
    fputcsv($output, [
        $q['id'],
        $q['nombre'],
        $q['apellido_paterno'],
        $q['apellido_materno'],
        $q['celular_1'],
        $q['celular_2'],
        $q['correo'],
        $q['seccion_electoral'],
        $q['calle'],
        $q['no_exterior'],
        $q['no_interior'],
        $q['colonia'],
        $q['municipio'],
        $q['codigo_postal'],
        $q['tipo'],
        $q['direccion'],
        $q['evidencia_path'],
        $q['created_at'],
        $q['estatus']
    ]);
}

fclose($output);
exit;
