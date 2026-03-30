<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') { header('Location: login.php'); exit(); }

// --- Fixed Egg Price per Egg ---
$egg_price = 5;

// --- Add Eggs Production ---
$add_egg_msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_eggs'])) {
    $date = $_POST['date'];
    $number = intval($_POST['number']);
    if ($number > 0) {
        $stmt = $pdo->prepare("INSERT INTO production (Date, NumberOfEggs) VALUES (?, ?)");
        $stmt->execute([$date, $number]);
        $add_egg_msg = "Egg production added successfully.";
    } else {
        $add_egg_msg = "Please enter a valid number of eggs.";
    }
}

// --- Fetch Data for Dashboard ---
$totalEggsProduced = (int)$pdo->query("SELECT IFNULL(SUM(NumberOfEggs),0) FROM production")->fetchColumn();
$totalEggsSold = (int)$pdo->query("SELECT IFNULL(SUM(Quantity),0) FROM orders WHERE Type='egg'")->fetchColumn();
$totalProfit = $totalEggsSold * $egg_price;
$eggsAvailable = $totalEggsProduced - $totalEggsSold;

// --- Fetch Batches and Sales ---
$batches = $pdo->query("SELECT * FROM production ORDER BY Date DESC")->fetchAll(PDO::FETCH_ASSOC);
$sales = $pdo->query("SELECT * FROM orders WHERE Type='egg' ORDER BY OrderDate DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Eggs Inventory | Admin | Poultry Management</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg,#f6d365 0%,#fda085 100%);
            font-family: 'Roboto',sans-serif;
        }
        .dashboard-box {
            border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,0.08);
            background: #fffbe7;
            padding:1.5rem 1.5rem 1.3rem 1.5rem;
            margin-bottom:1.2rem;
            font-family:'Montserrat',sans-serif;
            transition:box-shadow .2s;
        }
        .dashboard-box:hover {
            box-shadow:0 12px 36px rgba(0,0,0,0.16);
        }
        .stat-label {
            font-size:1.05rem; color:#f6931f; letter-spacing:.7px; font-weight:600;
        }
        .stat-value {
            font-size:2.2rem;font-weight:bold;color:#2d8659;
        }
        .section-card {
            border-radius:15px;
            box-shadow:0 6px 20px rgba(34,49,63,0.12);
            margin-bottom:2rem;
            background:#fff;
        }
        .section-card .card-body {padding:1.4rem 1.6rem;}
        h2,h4,h5 {font-family:'Montserrat',sans-serif;}
        .form-control:focus {border-color:#f6931f;box-shadow:0 0 0 0.2rem rgba(246,147,31,0.10);}
        .btn-primary, .btn-success, .btn-info {font-weight:600;}
        .table th {background:#f6f9ff;}
        .nav-shadow {box-shadow:0 2px 12px rgba(34,49,63,.08);}
        .icon-emoji {font-size:2rem;vertical-align:middle;}
        @media(max-width:767px){
            .stat-value {font-size:1.6rem;}
            .section-card .card-body {padding:1rem;}
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white nav-shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color:#f6931f;font-family:'Montserrat',sans-serif;" href="admin_dashboard.php">
            <span class="icon-emoji">🥚</span> Poultry Management
        </a>
        <div class="d-flex">
            <span class="navbar-text me-3">Hello, <span class="fw-bold" style="color:#27ae60;">Admin <?= htmlspecialchars($_SESSION['Username']); ?></span></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-4 mb-5">
    <h2 class="mb-4 text-success fw-bold"><span class="icon-emoji">🥚</span> Eggs Inventory</h2>
    <div class="row mb-2">
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Total Eggs Produced</div>
                <div class="stat-value"><?= $totalEggsProduced ?></div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Eggs Sold</div>
                <div class="stat-value"><?= $totalEggsSold ?></div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Eggs In Inventory</div>
                <div class="stat-value" style="color:#009688;"><?= $eggsAvailable ?></div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center" style="background:#e0f7fa;">
                <div class="stat-label">Total Profit (Eggs Sold)</div>
                <div class="stat-value" style="color:#00796b;">₹<?= number_format($totalProfit,2) ?></div>
            </div>
        </div>
    </div>

    <!-- Note about price -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">💸</span> Egg Price Per Egg</h4>
            <div class="alert alert-info">Egg price is fixed for all sales: <b>₹<?= $egg_price ?></b></div>
        </div>
    </div>

    <!-- Add Eggs Production -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">➕</span> Add Eggs Production</h4>
            <div class="alert alert-info mb-2" style="font-size:1.07em;">
                <b>Note:</b> Egg price per one egg is <span style="color:#1e7e34;">₹<?= $egg_price ?></span>
            </div>
            <?php if($add_egg_msg): ?><div class="alert alert-success"><?= $add_egg_msg ?></div><?php endif; ?>
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="number" class="form-control" min="1" placeholder="Number of Eggs" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="add_eggs" class="btn btn-success w-100">Add Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Eggs Production Records -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h5 class="mb-3"><span class="icon-emoji">📋</span> Eggs Production Records</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Number of Eggs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td><?= htmlspecialchars($batch['Date']); ?></td>
                            <td><?= htmlspecialchars($batch['NumberOfEggs']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="2" class="text-center">No eggs records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Egg Sales Records -->
    <div class="card section-card mb-4">
        <div class="card-body">
            <h5 class="mb-3"><span class="icon-emoji">💰</span> Egg Sales Records</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead>
                        <tr>
                            <th>Order Date</th>
                            <th>Eggs Sold</th>
                            <th>Total Revenue (₹)</th>
                            <th>Revenue per Egg (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($sale['OrderDate']))) ?></td>
                            <td><?= htmlspecialchars($sale['Quantity']) ?></td>
                            <td><?= number_format($sale['Quantity'] * $egg_price, 2) ?></td>
                            <td><?= number_format($egg_price, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="4" class="text-center">No egg sales records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="../admin/admin_dashboard.php" class="btn btn-link fs-5">← Back to Admin Dashboard</a>
    </div>
</div>
</body>
</html>