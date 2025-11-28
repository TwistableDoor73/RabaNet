<?php
session_start();
require_once '../Components/db.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['usr_type'] != 1) {
    header("Location: ../login.php");
    exit();
}

$mysqli = connectDatabase();
$error = '';
$success = '';

// Handle Create
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $usr_type = $_POST['usr_type'];
    $password = $_POST['password'];

    // Check Email
    $stmt = $mysqli->prepare("SELECT id_usr FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Este correo ya está registrado.";
    } else {
        $stmt->close();

        // Image Upload
        $profile_image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_name = basename($_FILES["profile_image"]["name"]);
            $target_file_fs = $target_dir . $file_name;
            $target_file_db = "uploads/" . $file_name;

            $check = getimagesize($_FILES["profile_image"]["tmp_name"]);

            if ($check !== false) {
                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file_fs)) {
                    $profile_image_path = $target_file_db;
                } else {
                    $error = "Error al subir la imagen.";
                }
            } else {
                $error = "El archivo no es una imagen.";
            }
        }

        if (empty($error)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $status = 1; // Active

            $query = "INSERT INTO users (full_name, email, phone, password_hash, usr_type, status, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("ssssiis", $full_name, $email, $phone, $password_hash, $usr_type, $status, $profile_image_path);

            if ($stmt->execute()) {
                $success = "Usuario creado correctamente.";
            } else {
                $error = "Error al crear usuario: " . $mysqli->error;
            }
            $stmt->close();
        }
    }
}

// Fetch Admin Data for Sidebar
$admin_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT full_name, email, profile_image FROM users WHERE id_usr = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_name, $admin_email, $admin_image);
$stmt->fetch();
$stmt->close();
$mysqli->close();

if (empty($admin_image)) {
    $admin_image = 'https://via.placeholder.com/150';
} else {
    if (!filter_var($admin_image, FILTER_VALIDATE_URL)) {
        $admin_image = '../' . $admin_image;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RabaNet: Agregar Usuario</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="dashboard-body">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>RabaNet</h2>
        </div>
        <div class="user-profile">
            <div class="profile-img-container">
                <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Profile" class="profile-img">
            </div>
            <h3><?php echo htmlspecialchars($admin_name); ?></h3>
            <p class="user-email"><?php echo htmlspecialchars($admin_email); ?></p>
            <a href="../logout.php" class="logout-btn">Cerrar Sesion</a>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="Dashboard.php" class="active">Usuarios</a></li>
            </ul>
        </nav>
    </aside>

    <main class="dashboard-main">
        <header class="main-header">
            <h1>Agregar Usuario</h1>
            <p>Crear un nuevo usuario en el sistema</p>
        </header>

        <div class="configuration-container" style="max-width: 800px; margin: 0 auto; width: 100%;">
            <div class="login-card"
                style="background-color: transparent; border: 2px solid white; color: white; max-width: 100%;">

                <form action="Add_User.php" method="post" enctype="multipart/form-data">

                    <div class="profile-upload-container">
                        <label for="profile_image" class="profile-upload-label">
                            <i class="fas fa-user-circle profile-upload-icon" id="upload-icon"
                                style="color: white;"></i>
                            <img id="image-preview" class="preview-image" src="#" alt="Profile Preview">
                            <input type="file" id="profile_image" name="profile_image" class="profile-upload-input"
                                accept="image/*" onchange="previewImage(this)">
                        </label>
                        <div class="register-header-text">
                            <h2 style="color: white;">Foto de Perfil</h2>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="full_name" style="color: white;">Nombre Completo</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone" style="color: white;">Numero de telefono</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="email" style="color: white;">Correo electronico</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password" style="color: white;">Contraseña</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="usr_type" style="color: white;">Tipo de Usuario</label>
                        <select id="usr_type" name="usr_type"
                            style="width: 100%; padding: 12px; border-radius: 8px; font-size: 16px;">
                            <option value="2" selected>Usuario</option>
                            <option value="1">Administrador</option>
                        </select>
                    </div>

                    <button type="submit" class="login-btn" style="border: 1px solid white;">Crear Usuario</button>
                    <a href="Dashboard.php" class="login-btn"
                        style="background: transparent; border: 1px solid white; display: inline-block; text-align: center; text-decoration: none; margin-left: 10px;">Cancelar</a>
                </form>
            </div>
        </div>
    </main>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('image-preview').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                    document.getElementById('upload-icon').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>