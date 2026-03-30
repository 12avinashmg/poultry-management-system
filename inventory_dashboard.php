<?php
session_start();
require_once 'includes/database.php';
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Total birds purchased
$totalBirds = $pdo->query("SELECT IFNULL(SUM(NumberOfBirds),0) AS total FROM birdspurchase")->fetch()['total'];

// Total birds sold (orders.Type='bird')
$totalBirdsSold = $pdo->query("SELECT IFNULL(SUM(Quantity),0) AS total FROM orders WHERE Type='bird'")->fetch()['total'];

// Total birds dead
$totalBirdsDead = $pdo->query("SELECT IFNULL(SUM(Deaths),0) AS total FROM birdsmortality")->fetch()['total'];

// Birds alive
$birdsAlive = $totalBirds - $totalBirdsSold - $totalBirdsDead;

// Eggs produced
$totalEggsProduced = $pdo->query("SELECT IFNULL(SUM(NumberOfEggs),0) AS total FROM production")->fetch()['total'];

// Eggs sold (orders.Type='egg')
$totalEggsSold = $pdo->query("SELECT IFNULL(SUM(Quantity),0) AS total FROM orders WHERE Type='egg'")->fetch()['total'];

// Eggs remaining
$eggsRemaining = $totalEggsProduced - $totalEggsSold;

// Feed purchased
$totalFeedPurchased = $pdo->query("SELECT IFNULL(SUM(Quantity),0) AS total FROM feedpurchase")->fetch()['total'];

// Feed used
$totalFeedUsed = $pdo->query("SELECT IFNULL(SUM(Quantity),0) AS total FROM feedconsumption")->fetch()['total'];

// Feed remaining
$feedRemaining = $totalFeedPurchased - $totalFeedUsed;
if ($feedRemaining < 0) $feedRemaining = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory Analytics | Admin | Poultry Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Poultry Management</a>
        <div class="d-flex">
            <span class="navbar-text me-3">Hello, Admin <?php echo htmlspecialchars($_SESSION['Username']); ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-5">
    <h2 class="mb-4 text-success">Poultry Inventory Analytics</h2>
    <div class="card">
        <div class="card-body">
            <canvas id="inventoryBar" height="120"></canvas>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-2"><div class="alert alert-primary text-center"><b>Eggs Remaining</b><br><?php echo $eggsRemaining; ?></div></div>
        <div class="col-md-2"><div class="alert alert-success text-center"><b>Eggs Sold</b><br><?php echo $totalEggsSold; ?></div></div>
        <div class="col-md-2"><div class="alert alert-info text-center"><b>Birds Alive</b><br><?php echo $birdsAlive; ?></div></div>
        <div class="col-md-2"><div class="alert alert-warning text-center"><b>Birds Sold</b><br><?php echo $totalBirdsSold; ?></div></div>
        <div class="col-md-2"><div class="alert alert-danger text-center"><b>Birds Dead</b><br><?php echo $totalBirdsDead; ?></div></div>
        <div class="col-md-1"><div class="alert alert-secondary text-center"><b>Feed Used</b><br><?php echo $totalFeedUsed; ?> kg</div></div>
        <div class="col-md-1"><div class="alert alert-secondary text-center"><b>Feed Remain</b><br><?php echo $feedRemaining; ?> kg</div></div>
    </div>
    <div class="mt-4">
        <a href="admin_dashboard.php" class="btn btn-link">← Back to Admin Dashboard</a>
    </div>
</div>
<script>
const ctx = document.getElementById('inventoryBar').getContext('2d');
const inventoryBar = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [
            'Eggs Remaining',
            'Eggs Sold',
            'Birds Alive',
            'Birds Sold',
            'Birds Dead',
            'Feed Used (kg)',
            'Feed Remaining (kg)'
        ],
        datasets: [{
            label: 'Inventory Analytics',
            data: [
                <?php echo $eggsRemaining; ?>,
                <?php echo $totalEggsSold; ?>,
                <?php echo $birdsAlive; ?>,
                <?php echo $totalBirdsSold; ?>,
                <?php echo $totalBirdsDead; ?>,
                <?php echo $totalFeedUsed; ?>,
                <?php echo $feedRemaining; ?>
            ],
            backgroundColor: [
                'rgba(13, 110, 253, 0.7)',
                'rgba(25, 135, 84, 0.7)',
                'rgba(23, 162, 184, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(220, 53, 69, 0.7)',
                'rgba(108, 117, 125, 0.7)',
                'rgba(108, 117, 125, 0.3)'
            ],
            borderColor: [
                'rgba(13, 110, 253, 1)',
                'rgba(25, 135, 84, 1)',
                'rgba(23, 162, 184, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(108, 117, 125, 1)',
                'rgba(108, 117, 125, 1)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>