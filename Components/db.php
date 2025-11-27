<?php
function connectDatabase()
{
    $host = 'localhost';
    $user = 'u661724798_Admin';
    $password = 'FEDJnosotros4@-_-';
    $dbname = 'u661724798_RabaNet';
    $port = 3306;

    try {
        $conn = new mysqli($host, $user, $password, $dbname, $port);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        die("Connection error: " . $e->getMessage());
    }
    return $conn;
}