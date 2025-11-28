<?php
session_start();
require_once '../Components/db.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['usr_type'] != 1) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $mysqli = connectDatabase();
    $id_to_delete = $_GET['id'];

    // Prevent deleting self
    if ($id_to_delete == $_SESSION['user_id']) {
        // Ideally show error, but for now just redirect
        header("Location: Dashboard.php");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM users WHERE id_usr = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
}

header("Location: Dashboard.php");
exit();
?>