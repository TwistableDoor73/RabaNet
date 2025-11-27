<?php
session_start();
require_once 'Components/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // Note: phone is not in the provided CREATE TABLE schema, but is in the image. I will assume it might be added or I should just handle it (maybe append to profile or ignore if no column). 
    // Wait, the user provided schema: id_usr, full_name, email, password_hash, usr_type, status, profile_image.
    // There is NO phone column. I will assume I should add it to the table or just not save it for now, but the UI has it. 
    // I'll proceed assuming I should save it if possible, but since schema is strict, I will skip saving phone to DB for now unless user updates schema, OR I can try to save it if the column exists. 
    // Actually, best practice is to ask, but I'll implement the UI and just not bind it to the insert for now to avoid errors, or maybe the user forgot to mention the column. 
    // Let's check the schema again: id_usr, full_name, email, password_hash, usr_type, status, profile_image.
    // I will NOT insert phone into DB to prevent SQL error, but I will keep the input in the form.

    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $usr_type = 2; // Default user
    $status = 1; // Default active

    // Image Upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $target_file;
            } else {
                $error = "Error al subir la imagen.";
            }
        } else {
            $error = "El archivo no es una imagen.";
        }
    }

    if (empty($error)) {
        $mysqli = connectDatabase();

        // Check if email exists
        $stmt = $mysqli->prepare("SELECT id_usr FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Este correo ya está registrado.";
        } else {
            // Insert user
            $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, password_hash, usr_type, status, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiis", $full_name, $email, $password_hash, $usr_type, $status, $profile_image);

            if ($stmt->execute()) {
                $success = "Registro exitoso. <a href='login.php'>Inicia sesión aquí</a>";
            } else {
                $error = "Error al registrar: " . $mysqli->error;
            }
        }
        $stmt->close();
        $mysqli->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RabaNet: Registro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Specific overrides for register page if needed, but reusing login styles mostly */
        .register-header-text {
            text-align: left;
            margin-left: 20px;
        }

        .register-header-text h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: black;
        }

        .register-header-text p {
            font-size: 14px;
            margin: 0;
            color: black;
        }

        .profile-upload-container {
            display: flex;
            align-items: center;
            justify-content: center;
            /* Centered as per design */
            margin-bottom: 20px;
            gap: 15px;
        }

        .profile-upload-label {
            cursor: pointer;
            position: relative;
            display: inline-block;
        }

        .profile-upload-icon {
            font-size: 80px;
            color: #2D5C7F;
            transition: opacity 0.3s;
        }

        .profile-upload-label:hover .profile-upload-icon {
            opacity: 0.7;
        }

        .profile-upload-input {
            display: none;
        }

        .preview-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            display: none;
        }
    </style>
</head>

<body class="login-body">
    <div class="login-header">
        <h1>RabaNet</h1>
    </div>

    <div class="login-container">
        <div class="login-card" style="max-width: 600px;"> <!-- Slightly wider for register form -->

            <form action="register.php" method="post" enctype="multipart/form-data">
                <div class="profile-upload-container">
                    <label for="profile_image" class="profile-upload-label">
                        <i class="fas fa-user-circle profile-upload-icon" id="upload-icon"></i>
                        <img id="image-preview" class="preview-image" src="#" alt="Profile Preview">
                        <input type="file" id="profile_image" name="profile_image" class="profile-upload-input"
                            accept="image/*" onchange="previewImage(this)">
                    </label>
                    <div class="register-header-text">
                        <h2>Bienvenido</h2>
                        <p>Listo para iniciar?</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"
                        style="color: green; background-color: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="full_name">Nombre Completo</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Numero de telefono</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="email">Correo electronico</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Registrarte</button>
            </form>
        </div>
    </div>

    <footer class="login-footer">
        <p>&copy; 2025 RabaNet</p>
    </footer>

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