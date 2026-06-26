<?php
require_once "includes/session.php";
require_once "db.php";

if (!isset($_SESSION["empleado_id"])) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT id, apoyo, nombre, apellido_paterno, apellido_materno, celular_1, celular_2, correo, calle, no_exterior, no_interior, colonia, municipio, codigo_postal, observaciones, estatus, created_at
        FROM registros_apoyos
        ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$registros = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=registros_apoyos.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Apoyos',
    'Nombre',
    'Apellido Paterno',
    'Apellido Materno',
    'Celular_1',
    'Celular_2',
    'Correo',
    'Calle',
    'No_Exterior',
    'No_Interior',
    'Colonia',
    'Municipio',
    'Codigo Postal',
    'Observaciones',
    'Estatus',
    'Fecha Registro'
]);

foreach ($registros as $r) {
    fputcsv($output, [
        $r['id'],
        $r['apoyo'],
        $r['nombre'],
        $r['apellido_paterno'],
        $r['apellido_materno'],
        $r['celular_1'],
         $r['celular_2'],
        $r['correo'],
        $r['calle'],
        $r['no_exterior'],
        $r['no_interior'],
        $r['colonia'],
        $r['municipio'],
        $r['codigo_postal'],
        $r['observaciones'],
        $r['estatus'],
        $r['created_at']
    ]);
}

fclose($output);
exit;