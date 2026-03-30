<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') { header('Location: login.php'); exit(); }

// --- Price Per KG Section ---
$price_per_kg = 50; // Default
$price_msg = "";
$ppkg_row = $pdo->query("SELECT value FROM settings WHERE name='feed_price_per_kg'")->fetch();
if ($ppkg_row) $price_per_kg = floatval($ppkg_row['value']);

// Handle price update
if (isset($_POST['set_price_per_kg'])) {
    $price_per_kg = floatval($_POST['price_per_kg']);
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('feed_price_per_kg', ?) ON DUPLICATE KEY UPDATE value=?");
    $stmt->execute([$price_per_kg, $price_per_kg]);
    $price_msg = "Price per KG updated!";
}

// --- Add Feed Purchased ---
$add_feed_msg = "";
if (isset($_POST['add_feed'])) {
    $date = $_POST['fdate'];
    $quantity = floatval($_POST['quantity']);
    $custom_price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $auto_price = $quantity * $price_per_kg;
    $final_price = $custom_price && $custom_price > 0 ? $custom_price : $auto_price;
    if ($quantity > 0) {
        $stmt = $pdo->prepare("INSERT INTO feedpurchase (Date, Quantity, Price) VALUES (?, ?, ?)");
        $stmt->execute([$date, $quantity, $final_price]);
        $add_feed_msg = "Feed purchase added.";
    }
}

// --- Add Feed Usage ---
$add_usage_msg = "";
if (isset($_POST['add_usage'])) {
    $date = $_POST['udate'];
    $quantity = floatval($_POST['used_quantity']);
    $custom_price = isset($_POST['used_price']) ? floatval($_POST['used_price']) : null;
    $employee = $_SESSION['User_ID'];
    $auto_price = $quantity * $price_per_kg;
    $final_price = $custom_price && $custom_price > 0 ? $custom_price : $auto_price;
    if ($quantity > 0) {
        $stmt = $pdo->prepare("INSERT INTO feedconsumption (ConsDate, Quantity, Price, Employee) VALUES (?, ?, ?, ?)");
        $stmt->execute([$date, $quantity, $final_price, $employee]);
        $add_usage_msg = "Feed usage recorded.";
    }
}

// --- Inventory Summary ---
$totalFeedPurchased = $pdo->query("SELECT IFNULL(SUM(Quantity),0) FROM feedpurchase")->fetchColumn();
$totalFeedUsed = $pdo->query("SELECT IFNULL(SUM(Quantity),0) FROM feedconsumption")->fetchColumn();
$feedRemaining = $totalFeedPurchased - $totalFeedUsed;

// --- Total Purchased Amount and Total Feed Usage Amount ---
$totalFeedPurchasedAmount = $pdo->query("SELECT IFNULL(SUM(Price),0) FROM feedpurchase")->fetchColumn();
$totalFeedUsedAmount = $pdo->query("SELECT IFNULL(SUM(Price),0) FROM feedconsumption")->fetchColumn();

// --- Fetch Feed Purchased Records ---
$purchases = $pdo->query("SELECT * FROM feedpurchase ORDER BY Date DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch Feed Consumed Records ---
$usages = $pdo->query("SELECT * FROM feedconsumption ORDER BY ConsDate DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feed Inventory & Consumption | Poultry Management</title>
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
        .btn-primary, .btn-warning, .btn-info {font-weight:600;}
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
            <span class="icon-emoji">🌾</span> Poultry Management
        </a>
        <div class="d-flex">
            <span class="navbar-text me-3">Hello, <span class="fw-bold" style="color:#27ae60;">Admin <?= htmlspecialchars($_SESSION['Username']); ?></span></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-4 mb-5">
    <h2 class="mb-4 text-success fw-bold"><span class="icon-emoji">🥗</span> Feed Inventory & Consumption</h2>
    <div class="row mb-2">
        <div class="col-12 col-md-4">
            <div class="dashboard-box text-center">
                <div class="stat-label">Feed Purchased (kg)</div>
                <div class="stat-value"><?= $totalFeedPurchased ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="dashboard-box text-center">
                <div class="stat-label">Feed Used (kg)</div>
                <div class="stat-value"><?= $totalFeedUsed ?></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="dashboard-box text-center">
                <div class="stat-label">Feed Remaining (kg)</div>
                <div class="stat-value"><?= $feedRemaining ?></div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="dashboard-box text-center" style="background:#e0f7fa;">
                <div class="stat-label">Total Purchased Amount</div>
                <div class="stat-value" style="color:#00796b;">₹<?= number_format($totalFeedPurchasedAmount,2) ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-box text-center" style="background:#fce4ec;">
                <div class="stat-label">Total Feed Usage Amount</div>
                <div class="stat-value" style="color:#ad1457;">₹<?= number_format($totalFeedUsedAmount,2) ?></div>
            </div>
        </div>
    </div>

    <!-- 1. Set Price Per KG -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">💸</span> Set Feed Price Per KG</h4>
            <?php if($price_msg): ?><div class="alert alert-success"><?= $price_msg ?></div><?php endif; ?>
            <form method="post" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="number" step="0.01" min="1" name="price_per_kg" class="form-control" value="<?= $price_per_kg ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="set_price_per_kg" class="btn btn-info w-100">Set Price</button>
                </div>
                <div class="col-md-6 align-self-center">
                    <span class="text-muted">Current Price per KG: <b>₹<?= $price_per_kg ?></b></span>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Add Feed Stock -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">➕</span> Add Feed Purchase</h4>
            <?php if($add_feed_msg): ?><div class="alert alert-success"><?= $add_feed_msg ?></div><?php endif; ?>
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <input type="date" name="fdate" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" placeholder="Quantity (kg)" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="Custom Total Price (optional)">
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_feed" class="btn btn-primary w-100">Add Feed</button>
                </div>
            </form>
            <small class="text-muted">If custom price is left blank, price is auto-calculated as: <b>Quantity × Price per KG</b></small>
        </div>
    </div>

    <!-- 3. Add Feed Usage -->
    <div class="card section-card mb-3">
        <div class="card-body">
            <h4 class="mb-3"><span class="icon-emoji">🥄</span> Add Feed Usage</h4>
            <?php if($add_usage_msg): ?><div class="alert alert-success"><?= $add_usage_msg ?></div><?php endif; ?>
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <input type="date" name="udate" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0.01" name="used_quantity" class="form-control" placeholder="Used Quantity (kg)" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0" name="used_price" class="form-control" placeholder="Custom Total Price (optional)">
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_usage" class="btn btn-warning w-100">Add Usage</button>
                </div>
            </form>
            <small class="text-muted">If custom price is left blank, price is auto-calculated as: <b>Quantity × Price per KG</b></small>
        </div>
    </div>

    <!-- Feed Purchased Details -->
    <div class="card section-card mb-4">
        <div class="card-body">
            <h5 class="mb-3"><span class="icon-emoji">📦</span> Feed Purchased Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:120px;">Date</th>
                            <th>Quantity (kg)</th>
                            <th>Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['Date']) ?></td>
                            <td><?= htmlspecialchars($p['Quantity']) ?></td>
                            <td><?= number_format($p['Price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No feed purchase records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Feed Consumed Details -->
    <div class="card section-card mb-4">
        <div class="card-body">
            <h5 class="mb-3"><span class="icon-emoji">🥄</span> Feed Consumed Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:120px;">Date</th>
                            <th>Quantity Used (kg)</th>
                            <th>Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usages as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['ConsDate']) ?></td>
                            <td><?= htmlspecialchars($u['Quantity']) ?></td>
                            <td><?= number_format($u['Price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usages)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No feed usage records found.</td>
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