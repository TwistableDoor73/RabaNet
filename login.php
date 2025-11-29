<?php
// session_start(); // Not needed for JWT
require_once 'Components/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $mysqli = connectDatabase();

    // Prevent SQL Injection
    $stmt = $mysqli->prepare("SELECT id_usr, password_hash, usr_type, failed_attempts, locked_until FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id_usr, $hashed_password, $usr_type, $failed_attempts, $locked_until);
            $stmt->fetch();

            // Check if account is locked
            if ($locked_until && strtotime($locked_until) > time()) {
                $remaining_time = ceil((strtotime($locked_until) - time()) / 60);
                $error = "Cuenta bloqueada. Intente nuevamente en " . $remaining_time . " minutos.";
            } else {
                // Verify password
                if (password_verify($password, $hashed_password)) {
                    // Reset failed attempts
                    $reset_stmt = $mysqli->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id_usr = ?");
                    $reset_stmt->bind_param("i", $id_usr);
                    $reset_stmt->execute();
                    $reset_stmt->close();

                    // Generate JWT
                    require_once 'Components/JWT.php';
                    $payload = [
                        'user_id' => $id_usr,
                        'usr_type' => $usr_type,
                        'exp' => time() + (60 * 60 * 24) // 1 day expiration
                    ];
                    $token = JWT::encode($payload);

                    // Set Cookie
                    setcookie("auth_token", $token, [
                        'expires' => time() + (60 * 60 * 24),
                        'path' => '/',
                        'secure' => false, // Set to true in production (HTTPS)
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);

                    if ($usr_type == 1) {
                        header("location: Admin/Dashboard.php");
                    } else {
                        header("location: User/User_Dashboard.php");
                    }
                    exit();
                } else {
                    // Increment failed attempts
                    $failed_attempts++;
                    $lock_time = null;
                    if ($failed_attempts >= 3) {
                        $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $error = "Cuenta bloqueada por 15 minutos debido a múltiples intentos fallidos.";
                    } else {
                        $error = "Contraseña incorrecta. Intentos restantes: " . (3 - $failed_attempts);
                    }

                    $update_stmt = $mysqli->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id_usr = ?");
                    $update_stmt->bind_param("isi", $failed_attempts, $lock_time, $id_usr);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        } else {
            $error = "No existe una cuenta con este correo.";
        }
        $stmt->close();
    } else {
        $error = "Error en la consulta: " . $mysqli->error;
    }
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RabaNet: Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="login-body">
    <div class="login-header">
        <h1>RabaNet</h1>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2>Bienvenido</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="email">Correo electronico</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Iniciar Sesión</button>
                
                <div style="margin-top: 15px; text-align: center; color: white; font-size: 14px;">
                    ¿No tienes una cuenta? <a href="register.php" style="color: white; text-decoration: underline; font-weight: bold;">Regístrate aquí</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="login-footer">
        <p>&copy; 2025 RabaNet</p>
    </footer>
</body>

</html>