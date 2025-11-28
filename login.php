<?php
session_start();
require_once 'Components/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $mysqli = connectDatabase();

    // Prevent SQL Injection
    $stmt = $mysqli->prepare("SELECT id_usr, password_hash, usr_type FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id_usr, $hashed_password, $usr_type);
            $stmt->fetch();

            // Verify password (assuming password_hash was used, otherwise use plain comparison if legacy)
            // For this example, I'll use password_verify. If your DB has plain text, change to: if ($password === $hashed_password)
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id_usr;
                $_SESSION['usr_type'] = $usr_type;
                if ($usr_type == 1) {
                    header("location: Admin/Dashboard.php");
                } else {
                    header("location: User/User_Dashboard.php");
                }
                exit();
            } else {
                $error = "Contraseña incorrecta.";
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
            </form>
        </div>
    </div>

    <footer class="login-footer">
        <p>&copy; 2025 RabaNet</p>
    </footer>
</body>

</html>