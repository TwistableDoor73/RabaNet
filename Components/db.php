<?php
function connectDatabase()
{
    $host = 'localhost';
    $user = 'root';
    $password = 'FEDJnosotros4@-_-';
    $dbname = 'RabaNet';
    $port = 3306;
    $socket = '/tmp/mysql.sock';

    function checkUserType($requiredUserType = 2)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }

        // Depuración: Imprime los valores actuales
        error_log("User ID: " . $_SESSION['user_id']);
        error_log("User Type: " . $_SESSION['usr_type']);
        error_log("Required Type: " . $requiredUserType);

        if (!isset($_SESSION['usr_type']) || (int) $_SESSION['usr_type'] !== (int) $requiredUserType) {
            echo "<p>No tienes permiso para acceder a esta página.</p>";
            exit();
        }
    }

    $mysqli = new mysqli(hostname: $host, username: $user, password: $password, database: $dbname, port: $port, socket: $socket);

    if ($mysqli->connect_errno) {
        die("Connection error: " . $mysqli->connect_error);
    }
    return $mysqli;
}