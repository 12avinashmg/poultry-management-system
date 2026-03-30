<?php
session_start();
require_once 'includes/database.php';

if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch all users with at least one order
$users = $pdo->query("
    SELECT DISTINCT u.User_ID, u.Username, a.AddressLine1, a.City, a.State, a.Pincode, a.Country, a.Phone
    FROM user u
    JOIN orders o ON u.User_ID = o.User_ID
    LEFT JOIN (
        SELECT a1.*
        FROM addresses a1
        INNER JOIN (
            SELECT User_ID, MAX(IsDefault) as IsDefault, MAX(CreatedAt) as CreatedAt
            FROM addresses
            GROUP BY User_ID
        ) a2 ON a1.User_ID = a2.User_ID AND (a1.IsDefault = a2.IsDefault OR a1.CreatedAt = a2.CreatedAt)
    ) a ON u.User_ID = a.User_ID
    ORDER BY u.Username
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchases | Poultry Management Admin</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg,#43cea2 0%,#185a9d 100%); font-family: 'Roboto',sans-serif; }
        .user-section { background:#f6f9ff; border-radius:15px; box-shadow:0 4px 18px rgba(34,49,63,0.09); padding:1.7em 1.2em 1.5em 1.2em; margin-bottom:2em; }
        .username-header {font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.3rem;color:#2980b9;}
        .address-block {font-size:1.07em;color:#444;margin-bottom:1em;}
        .phone-badge {background:#d1ecf1;color:#0c5460;font-weight:600;border-radius:14px;padding:3px 11px;font-size:.97em;margin-top:3px;display:inline-block;}
        .orders-table th {background:#e3f2fd;}
    </style>
</head>
<body>
<div class="container my-4">
    <h2 class="mb-4 text-primary fw-bold"><span style="font-size:1.5em;">🛒</span> All User Purchases </h2>
    <?php foreach ($users as $user): ?>
        <div class="user-section">
            <div class="username-header mb-2">👤 <?= htmlspecialchars($user['Username']) ?></div>
            <div class="address-block">
                <?= htmlspecialchars($user['AddressLine1']) ?><br>
                <?= htmlspecialchars($user['City']) ?>, <?= htmlspecialchars($user['State']) ?>, <?= htmlspecialchars($user['Pincode']) ?><br>
                <?= htmlspecialchars($user['Country']) ?>
                <?php if (!empty($user['Phone'])): ?>
                    <br><span class="phone-badge">📞 <?= htmlspecialchars($user['Phone']) ?></span>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle orders-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order Type</th>
                            <th>Quantity</th>
                            <th>Amount (₹)</th>
                            <th>Price/Unit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $orders = $pdo->prepare("SELECT * FROM orders WHERE User_ID = ? ORDER BY OrderDate DESC");
                        $orders->execute([$user['User_ID']]);
                        foreach ($orders as $order):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['OrderDate']))) ?></td>
                            <td><?= htmlspecialchars(ucfirst($order['Type'])) ?></td>
                            <td><?= (int)$order['Quantity'] ?></td>
                            <td>₹<?= number_format($order['TotalPrice'], 2) ?></td>
                            <td><?= ($order['Quantity'] > 0) ? number_format($order['TotalPrice']/$order['Quantity'],2) : '0.00' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="text-center mt-4">
        <a href="admin_dashboard.php" class="btn btn-link fs-5">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>