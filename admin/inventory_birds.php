<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') { header('Location: login.php'); exit(); }

// --- Set Bird Price Per Bird (Fixed for all) ---
$price_per_bird = 200; // Always 200, not editable in UI

// --- Add New Birds ---
$add_bird_msg = "";
if (isset($_POST['add_birds'])) {
    $date = $_POST['bdate'];
    $num = intval($_POST['number']);
    if ($num > 0) {
        $final_price = $num * $price_per_bird;
        $stmt = $pdo->prepare("INSERT INTO birdspurchase (Date, NumberOfBirds, Price) VALUES (?, ?, ?)");
        $stmt->execute([$date, $num, $final_price]);
        $add_bird_msg = "Birds added successfully.";
    } else {
        $add_bird_msg = "Please enter a valid number.";
    }
}

// --- Add Bird Deaths ---
$add_death_msg = "";
if (isset($_POST['add_mortality'])) {
    $date = $_POST['ddate'];
    $deaths = intval($_POST['deaths']);
    if ($deaths > 0) {
        $stmt = $pdo->prepare("INSERT INTO birdsmortality (Date, Deaths) VALUES (?, ?)");
        $stmt->execute([$date, $deaths]);
        $add_death_msg = "Bird death(s) recorded.";
    } else {
        $add_death_msg = "Please enter a valid number of deaths.";
    }
}

// --- Get All Purchases ---
$purchases = $pdo->query("SELECT Date, NumberOfBirds FROM birdspurchase ORDER BY Date ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Get Total Sold and Dead Birds ---
$totalBirdsSold = (int)$pdo->query("SELECT IFNULL(SUM(Quantity),0) FROM orders WHERE Type='bird'")->fetchColumn();
$totalBirdsSoldAmount = $pdo->query("SELECT IFNULL(SUM(TotalPrice),0) FROM orders WHERE Type='bird'")->fetchColumn();
$totalBirdsDead = (int)$pdo->query("SELECT IFNULL(SUM(Deaths),0) FROM birdsmortality")->fetchColumn();

// --- Calculate Totals ---
$totalBirds = 0;
foreach ($purchases as $row) {
    $totalBirds += $row['NumberOfBirds'];
}
$totalPurchaseAmount = $totalBirds * $price_per_bird;
$birdsAlive = $totalBirds - $totalBirdsSold - $totalBirdsDead;
$totalLossAmount = $totalBirdsDead * $price_per_bird;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Birds Inventory & Mortality</title>
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
            border-radius:16px;box-shadow:0 8px 28px rgba(0,0,0,0.09);
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
        .btn-primary, .btn-danger {font-weight:600;}
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
            <span class="icon-emoji">🐦</span> Poultry Management
        </a>
        <div class="d-flex">
            <span class="navbar-text me-3">Hello, <span class="fw-bold" style="color:#27ae60;">Admin <?= htmlspecialchars($_SESSION['Username']); ?></span></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-4 mb-5">
    <h2 class="mb-4 text-success fw-bold"><span class="icon-emoji">🐥</span> Birds Inventory & Mortality</h2>
    <div class="row mb-2">
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Purchased</div>
                <div class="stat-value"><?= $totalBirds ?></div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Sold</div>
                <div class="stat-value"><?= $totalBirdsSold ?></div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Died</div>
                <div class="stat-value" style="color:#e53935;"><?= $totalBirdsDead ?></div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="dashboard-box text-center">
                <div class="stat-label">Alive</div>
                <div class="stat-value" style="color:#009688;"><?= $birdsAlive ?></div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="dashboard-box text-center" style="background:#e0f7fa;">
                <div class="stat-label">Total Purchased Amount</div>
                <div class="stat-value" style="color:#00796b;">₹<?= number_format($totalPurchaseAmount,2) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-box text-center" style="background:#e3f2fd;">
                <div class="stat-label">Total Sold Amount</div>
                <div class="stat-value" style="color:#1976d2;">₹<?= number_format($totalBirdsSoldAmount,2) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-box text-center" style="background:#ffebee;">
                <div class="stat-label">Total Loss (Dead Birds)</div>
                <div class="stat-value" style="color:#d32f2f;">₹<?= number_format($totalLossAmount,2) ?></div>
            </div>
        </div>
    </div>

    <div class="card section-card mb-3">
        <div class="card-body">
            <h5 class="mb-3"><span class="icon-emoji">📋</span> Bird Purchase Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bought</th>
                            <th>Price per Bird (₹)</th>
                            <th>Batch Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($purchases as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Date']) ?></td>
                            <td><?= $row['NumberOfBirds'] ?></td>
                            <td><?= number_format($price_per_bird,2) ?></td>
                            <td><?= number_format($row['NumberOfBirds'] * $price_per_bird,2) ?></td>
                        </tr>
                        <?php endforeach; if(empty($purchases)): ?>
                            <tr><td colspan="4" class="text-center">No purchase records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th>Total</th>
                            <th><?= $totalBirds ?></th>
                            <th><?= number_format($price_per_bird,2) ?></th>
                            <th><?= number_format($totalPurchaseAmount,2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- 1. Set Price Per Bird (Fixed, not editable) -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">💸</span> Bird Price Per Bird</h4>
            <div class="alert alert-info">Bird price is fixed for all batches: <b>₹<?= $price_per_bird ?></b></div>
        </div>
    </div>

    <!-- 2. Add New Birds -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">➕</span> Add New Birds</h4>
            <?php if($add_bird_msg): ?><div class="alert alert-success"><?= $add_bird_msg ?></div><?php endif; ?>
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <input type="date" name="bdate" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <input type="number" min="1" name="number" class="form-control" placeholder="No. of Birds" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="add_birds" class="btn btn-primary w-100">Add Birds</button>
                </div>
            </form>
            <small class="text-muted">Bird price is fixed: <b>Number × ₹<?= $price_per_bird ?></b></small>
        </div>
    </div>

    <!-- 3. Add Bird Deaths -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">☠️</span> Add Bird Deaths</h4>
            <?php if($add_death_msg): ?><div class="alert alert-success"><?= $add_death_msg ?></div><?php endif; ?>
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <input type="date" name="ddate" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <input type="number" min="1" name="deaths" class="form-control" placeholder="Number of Deaths" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="add_mortality" class="btn btn-danger w-100">Add Deaths</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="../admin/admin_dashboard.php" class="btn btn-link fs-5">← Back to Admin Dashboard</a>
    </div>
</div>
</body>
</html>