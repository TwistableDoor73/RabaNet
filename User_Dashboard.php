<?php
session_start();
require_once 'Components/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mysqli = connectDatabase();
$user_id = $_SESSION['user_id'];

// Fetch User Data
$stmt = $mysqli->prepare("SELECT full_name, email, phone, profile_image FROM users WHERE id_usr = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $profile_image);
$stmt->fetch();
$stmt->close();

// Default profile image if none set
if (empty($profile_image)) {
    $profile_image = 'https://via.placeholder.com/150'; // Or a local default asset
}

// Fetch Latest Sensor Reading
$query = "SELECT temperatura, humedad_aire, humedad_suelo, luz, fecha FROM sensor_readings ORDER BY fecha DESC LIMIT 1";
$result = $mysqli->query($query);

$sensor_data = [
    'temperatura' => '--',
    'humedad_aire' => '--',
    'humedad_suelo' => '--',
    'luz' => '--'
];

if ($result && $result->num_rows > 0) {
    $sensor_data = $result->fetch_assoc();
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RabaNet: Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="dashboard-body">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>RabaNet</h2>
        </div>
        <div class="user-profile">
            <div class="profile-img-container">
                <?php if ($profile_image && file_exists($profile_image)): ?>
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
            <a href="logout.php" class="logout-btn">Cerrar Sesion</a>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="#" class="active">Monitoreo</a></li>
                <li><a href="#">Configuracion</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <header class="main-header">
            <h1>Centro de monitoreo</h1>
            <p>Estado actual de tu invernadero</p>
        </header>

        <div class="dashboard-grid">
            <!-- Sensor Cards -->
            <div class="card temp-air">
                <h3>Temperatura (Aire)</h3>
                <div class="value"><?php echo number_format($sensor_data['temperatura'], 0); ?>°</div>
            </div>
            <div class="card humidity-air">
                <h3>Humedad (Aire)</h3>
                <div class="value"><?php echo number_format($sensor_data['humedad_aire'], 0); ?>%</div>
            </div>
            <div class="card light">
                <h3>Luz</h3>
                <div class="value"><?php echo number_format($sensor_data['luz'], 0); ?><span class="unit">lumens</span>
                </div>
            </div>

            <!-- Predictions Graph -->
            <div class="card predictions">
                <h3>Predicciones</h3>
                <div class="chart-container">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- More Sensor Cards -->
            <div class="card temp-soil">
                <h3>Temperatura (Suelo)</h3>
                <!-- Assuming soil temp is similar or derived, or if schema implies it. 
                     The schema has 'temperatura', 'humedad_aire', 'humedad_suelo', 'luz'. 
                     It does NOT have 'temperatura_suelo'. 
                     I will use a placeholder or maybe the user meant 'humedad_suelo' is the only soil metric.
                     Wait, the image shows "Temperatura (Suelo) 20°". 
                     The schema ONLY has `temperatura` (decimal). I'll assume that's air temp.
                     I will display a placeholder for Soil Temp for now as it's not in the provided schema.
                -->
                <div class="value">20°</div> <!-- Placeholder as per image, since DB col missing -->
            </div>
            <div class="card humidity-soil">
                <h3>Humedad (Suelo)</h3>
                <div class="value"><?php echo number_format($sensor_data['humedad_suelo'], 0); ?>°</div>
                <!-- Image shows degrees? Usually % for humidity. Image says 50°. I'll follow image but % is standard. Image definitely says 50°. -->
            </div>
        </div>

        <footer class="dashboard-footer">
            <p>Estos datos se actualizan cada 10 minutos</p>
        </footer>
    </main>

    <script>
        // Chart.js for Predictions
        const ctx = document.getElementById('growthChart').getContext('2d');
        const growthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'],
                datasets: [{
                    label: 'Crecimiento Estimado',
                    data: [10, 15, 25, 30, 45, 55, 65], // Dummy data representing growth curve
                    borderColor: '#4CAF50', // Green line
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#ccc'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#ccc'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>