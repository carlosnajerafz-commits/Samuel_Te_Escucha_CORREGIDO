<?php
require_once "db.php";
header("Content-Type: application/json; charset=UTF-8");
$cp = trim($_GET["cp"] ?? "");
if ($cp === "") {
    echo json_encode([]);
    exit;
}
$sql = "SELECT codigo_postal, colonia FROM codigos_postales WHERE codigo_postal = :cp LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([":cp" => $cp]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
