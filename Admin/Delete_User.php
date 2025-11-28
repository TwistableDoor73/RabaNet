<?php
require_once '../Components/AuthMiddleware.php';
$user = checkAuth(1); // Verify JWT and Admin access
$admin_id = $user['user_id'];

require_once '../Components/db.php';

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