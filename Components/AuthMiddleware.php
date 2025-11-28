<?php
require_once 'JWT.php';

function checkAuth($requiredType = null)
{
    if (!isset($_COOKIE['auth_token'])) {
        header("Location: ../login.php");
        exit();
    }

    $token = $_COOKIE['auth_token'];
    $payload = JWT::decode($token);

    if (!$payload) {
        // Invalid token
        setcookie("auth_token", "", time() - 3600, "/"); // Clear cookie
        header("Location: ../login.php");
        exit();
    }

    // Check expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        setcookie("auth_token", "", time() - 3600, "/"); // Clear cookie
        header("Location: ../login.php");
        exit();
    }

    // Check User Type if required
    if ($requiredType !== null && $payload['usr_type'] != $requiredType) {
        // Unauthorized access level
        header("Location: ../login.php"); // Or an error page
        exit();
    }

    return $payload;
}
