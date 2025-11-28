<?php
require_once '../Components/AuthMiddleware.php';
$user = checkAuth(1); // Verify JWT and Admin access
$admin_id = $user['user_id'];

require_once '../Components/db.php';

$mysqli = connectDatabase();
$user_id = $_SESSION['user_id'];

// Fetch Current Admin Data for Sidebar
$stmt = $mysqli->prepare("SELECT full_name, email, phone, profile_image FROM users WHERE id_usr = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($admin_name, $admin_email, $admin_phone, $admin_image);
$stmt->fetch();
$stmt->close();

// Default profile image
if (empty($admin_image)) {
    $admin_image = 'https://via.placeholder.com/150';
} else {
    if (!filter_var($admin_image, FILTER_VALIDATE_URL)) {
        $admin_image = '../' . $admin_image;
    }
}

// Fetch All Users
$query = "SELECT id_usr, full_name, email, usr_type FROM users";
$result = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RabaNet: Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Table Styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            color: white;
        }

        .user-table th,
        .user-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-table th {
            background-color: rgba(255, 255, 255, 0.05);
            font-weight: 700;
        }

        .user-table tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            margin-right: 5px;
        }

        .edit-btn {
            background-color: #4CAF50;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .add-user-btn {
            display: inline-block;
            background-color: #2F4F3A;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            border: 1px solid white;
        }

        .add-user-btn:hover {
            background-color: white;
            color: #2F4F3A;
        }
    </style>
</head>

<body class="dashboard-body">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>RabaNet</h2>
        </div>
        <div class="user-profile">
            <div class="profile-img-container">
                <?php if ($admin_image && (file_exists($admin_image) || filter_var($admin_image, FILTER_VALIDATE_URL))): ?>
                    <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Profile" class="profile-img">
                <?php else: ?>
                    <i class="fas fa-user-circle profile-icon-placeholder"></i>
                <?php endif; ?>
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

    <!-- Main Content -->
    <main class="dashboard-main">
        <header class="main-header">
            <h1>Gestion de Usuarios</h1>
            <p>Administra los usuarios del sistema</p>
        </header>

        <div class="dashboard-content">
            <a href="Add_User.php" class="add-user-btn"><i class="fas fa-plus"></i> Agregar Usuario</a>

            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_usr']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php echo ($row['usr_type'] == 1) ? 'Admin' : 'Usuario'; ?>
                            </td>
                            <td>
                                <a href="Edit_User.php?id=<?php echo $row['id_usr']; ?>" class="action-btn edit-btn"><i
                                        class="fas fa-edit"></i></a>
                                <a href="Delete_User.php?id=<?php echo $row['id_usr']; ?>" class="action-btn delete-btn"
                                    onclick="return confirm('¿Estas seguro de eliminar este usuario?');"><i
                                        class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>
<?php $mysqli->close(); ?>