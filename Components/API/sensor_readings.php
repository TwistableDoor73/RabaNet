<?php
file_put_contents("log.txt", "RAW: " . file_get_contents("php://input") . "\n", FILE_APPEND);

require_once __DIR__ . "/../db.php";
header("Content-Type: application/json");

// Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "msg" => "JSON inválido"]);
    exit;
}

$conn = connectDatabase();

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "msg" => "Error conexión BD", "mysql" => $conn->connect_error]);
    exit;
}

// Variables
$temperatura = $data["temperatura"];
$humedadAire = $data["humedad_aire"];
$humedadSuelo = $data["humedad_suelo"];
$luz = $data["luz"];
$lampara = $data["lampara_estado"];
$bomba = $data["bomba_estado"];

// Insert
$stmt = $conn->prepare("
    INSERT INTO sensor_readings 
    (temperatura, humedad_aire, humedad_suelo, luz, lampara_estado, bomba_estado)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ddddii", $temperatura, $humedadAire, $humedadSuelo, $luz, $lampara, $bomba);
$stmt->execute();

// Checar errores SQL
if ($stmt->error) {
    echo json_encode(["status" => "error", "sql_error" => $stmt->error]);
    exit;
}

echo json_encode(["status" => "ok"]);