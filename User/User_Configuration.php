<?php
require_once '../Components/AuthMiddleware.php';
$user = checkAuth(); // Verify JWT
$user_id = $user['user_id'];

require_once '../Components/db.php';
$mysqli = connectDatabase();
$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (!empty($new_password) && (strlen($new_password) <= 8 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W_]/', $new_password))) {
        $error = "La contraseña debe tener más de 8 caracteres, incluir al menos un número y un carácter especial.";
    } else {
        // Image Upload
        $profile_image_path = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_name = basename($_FILES["profile_image"]["name"]);
            $target_file_fs = $target_dir . $file_name;
            $target_file_db = "uploads/" . $file_name; // Store relative to root

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
            // Build Update Query
            $query = "UPDATE users SET full_name = ?, email = ?, phone = ?";
            $types = "sss";
            $params = [$full_name, $email, $phone];

            if ($profile_image_path) {
                $query .= ", profile_image = ?";
                $types .= "s";
                $params[] = $profile_image_path;
            }

            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $query .= ", password_hash = ?";
                $types .= "s";
                $params[] = $password_hash;
            }

            $query .= " WHERE id_usr = ?";
            $types .= "i";
            $params[] = $user_id;

            $stmt = $mysqli->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $success = "Perfil actualizado correctamente.";
            } else {
                $error = "Error al actualizar el perfil: " . $mysqli->error;
            }
            $stmt->close();
        }
    }
}

// Fetch User Data (Always fetch fresh data)
$stmt = $mysqli->prepare("SELECT full_name, email, phone, profile_image FROM users WHERE id_usr = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $profile_image);
$stmt->fetch();
$stmt->close();
$mysqli->close();

// Default profile image
if (empty($profile_image)) {
    $profile_image = 'https://via.placeholder.com/150'; // Or a local default asset
} else {
    // Adjust path for User directory
    if (!filter_var($profile_image, FILTER_VALIDATE_URL)) {
        $profile_image = '../' . $profile_image;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RabaNet: Configuracion</title>
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
                <?php if ($profile_image && (file_exists($profile_image) || filter_var($profile_image, FILTER_VALIDATE_URL))): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-img">
                <?php else: ?>
                    <i class="fas fa-user-circle profile-icon-placeholder"></i>
                <?php endif; ?>
            </div>
            <h3><?php echo htmlspecialchars($full_name); ?></h3>
            <p class="user-email"><?php echo htmlspecialchars($email); ?></p>
            <?php if ($phone): ?>
                <p class="user-phone"><?php echo htmlspecialchars($phone); ?></p>
            <?php endif; ?>
            <a href="../logout.php" class="logout-btn">Cerrar Sesion</a>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="User_Dashboard.php">Monitoreo</a></li>
                <li><a href="User_Configuration.php" class="active">Configuracion</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <header class="main-header">
            <h1>Configuracion de Perfil</h1>
            <p>Actualiza tus datos personales</p>
        </header>

        <div class="configuration-container" style="max-width: 800px; margin: 0 auto; width: 100%;">
            <div class="login-card"
                style="background-color: transparent; border: 2px solid white; color: white; max-width: 100%;">

                <form action="User_Configuration.php" method="post" enctype="multipart/form-data">
                    <div class="profile-upload-container">
                        <label for="profile_image" class="profile-upload-label">
                            <?php if ($profile_image && (file_exists($profile_image) || filter_var($profile_image, FILTER_VALIDATE_URL))): ?>
                                <img id="image-preview" class="preview-image"
                                    src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Preview"
                                    style="display: block;">
                                <i class="fas fa-user-circle profile-upload-icon" id="upload-icon"
                                    style="display: none;"></i>
                            <?php else: ?>
                                <i class="fas fa-user-circle profile-upload-icon" id="upload-icon"
                                    style="color: white;"></i>
                                <img id="image-preview" class="preview-image" src="#" alt="Profile Preview">
                            <?php endif; ?>

                            <input type="file" id="profile_image" name="profile_image" class="profile-upload-input"
                                accept="image/*" onchange="previewImage(this)">
                        </label>
                        <div class="register-header-text">
                            <h2 style="color: white;">Foto de Perfil</h2>
                            <p style="color: #ccc;">Haz click para cambiar</p>
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
                        <input type="text" id="full_name" name="full_name"
                            value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone" style="color: white;">Numero de telefono</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email" style="color: white;">Correo electronico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                            required>
                    </div>

                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.2); margin: 30px 0;">

                    <div class="form-group">
                        <label for="password" style="color: white;">Nueva Contraseña (Opcional)</label>
                        <input type="password" id="password" name="password"
                            placeholder="Dejar en blanco para mantener la actual">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" style="color: white;">Confirmar Contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <button type="submit" class="login-btn" style="border: 1px solid white;">Guardar Cambios</button>
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
                    var icon = document.getElementById('upload-icon');
                    if (icon) icon.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>