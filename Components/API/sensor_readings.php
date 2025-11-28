<?php
require_once "Components/db.php";
header("Content-Type: application/json");

// Leer JSON del Arduino
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "msg" => "JSON inválido"]);
    exit;
}

$conn = connectDatabase();

// Variables recibidas
$temperatura = $data["temperatura"];
$humedadAire = $data["humedad_aire"];
$humedadSuelo = $data["humedad_suelo"];
$luz = $data["luz"];
$lampara = $data["lampara_estado"];
$bomba = $data["bomba_estado"];

// Insertar en BD
$stmt = $conn->prepare("
    INSERT INTO lecturas 
    (temperatura, humedad_aire, humedad_suelo, luz, lampara, bomba)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ddddii", $temperatura, $humedadAire, $humedadSuelo, $luz, $lampara, $bomba);
$stmt->execute();

echo json_encode(["status" => "ok"]);