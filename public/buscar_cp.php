<?php
require_once "db.php";
header("Content-Type: application/json; charset=UTF-8");

$cp = trim($_GET["cp"] ?? "");

if ($cp === "" || !preg_match('/^[0-9]{5}$/', $cp)) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT colonia, municipio
    FROM codigos_postales
    WHERE codigo_postal = :cp
    ORDER BY colonia ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([":cp" => $cp]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo json_encode([]);
    exit;
}

$colonias  = array_column($rows, "colonia");
$municipio = $rows[0]["municipio"];

echo json_encode([
    "colonias"  => $colonias,
    "municipio" => $municipio
], JSON_UNESCAPED_UNICODE);
