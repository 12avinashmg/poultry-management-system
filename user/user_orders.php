<?php
session_start();
require_once 'includes/database.php';
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'user') { header('Location: login.php'); exit(); }
$user_id = $_SESSION['User_ID'];
$orders = $pdo->prepare("SELECT * FROM orders WHERE User_ID=? ORDER BY OrderDate DESC");
$orders->execute([$user_id]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders | Poultry</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #f6d365 0%, #fda085 100%);
            min-height: 100vh;
        }
        .orders-card {
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            background: #fffbe7;
            padding: 2.5rem 2rem 2rem 2rem;
            margin-top: 3.5rem;
            margin-bottom: 2.5rem;
            max-width: 600px;
        }
        .special-greeting {
            font-size: 2rem;
            font-weight: 700;
            color: #ff7e5f;
            letter-spacing: 1px;
            margin-bottom: 0.5em;
        }
        .special-sub {
            font-size: 1.25rem;
            color: #fcaf3e;
            margin-bottom: 2em;
        }
        .back-btn {
            font-size: 1.05rem;
            font-weight: 500;
            border-radius: 10px;
            padding: 0.5em 1.8em;
            background: #20bf6b;
            color: #fff;
            border: none;
            margin-top: 1em;
            box-shadow: 0 2px 8px rgba(32,191,107,0.10);
            transition: background 0.18s, color 0.18s;
            text-decoration: none;
            display: inline-block;
        }
        .back-btn:hover {
            background: #0fb96a;
            color: #fff;
        }
        .order-table th {
            background: #fceabb;
            color: #d35400;
        }
        .order-table td, .order-table th {
            vertical-align: middle;
        }
        .no-orders {
            color: #888;
            font-size: 1.1rem;
        }
        .emoji {
            font-size: 2.2rem;
            margin-right: 0.4em;
        }
        .footer-message {
            color: #d35400;
            font-size: 1.13rem;
            margin-top: 2.2em;
        }
    </style>
</head>
<body>
<div class="d-flex justify-content-center">
    <div class="orders-card">
        <div class="text-center">
            <div class="special-greeting">
                <span class="emoji">🙏</span>Thank You!
            </div>
            <div class="special-sub">
                We appreciate your trust in us. Your orders help us grow!<br>
                <span style="font-size:1.55rem;">🐦🥚</span>
            </div>
        </div>
        <h4 class="text-center mb-4" style="color:#d35400; font-weight:600;">📦 My Orders</h4>
        <table class="table table-bordered order-table mb-4">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['OrderDate']) ?></td>
                    <td><?= ucfirst($order['Type']) ?></td>
                    <td><?= (int)$order['Quantity'] ?></td>
                    <td>₹<?= number_format($order['TotalPrice'],2) ?></td>
                </tr>
                <?php endforeach; if(empty($orders)): ?>
                <tr>
                    <td colspan="4" class="text-center no-orders">You have not placed any orders yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="text-center">
            <a href="user_dashboard.php" class="back-btn">⬅️ Back to Menu</a>
        </div>
        <div class="footer-message text-center mt-4">
            <span class="emoji">💖</span>Thanks for being a valued part of our poultry family.<br>
            <span style="font-size:1.15rem;">Visit again soon!</span>
        </div>
    </div>
</div>
</body>
</html>