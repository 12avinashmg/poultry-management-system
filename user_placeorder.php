<?php
session_start();
require_once 'includes/database.php';
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'user') {
    header('Location: login.php');
    exit();
}

$message = "";

// Get prices set by admin (per bird)
$bird_price_per_bird = 200; // default fallback
$row = $pdo->query("SELECT value FROM settings WHERE name='bird_price_per_bird'")->fetch();
if ($row) $bird_price_per_bird = floatval($row['value']);

// Get price per egg
$egg_price_per_piece = 5; // default fallback
$row = $pdo->query("SELECT value FROM settings WHERE name='egg_price_per_piece'")->fetch();
if ($row) $egg_price_per_piece = floatval($row['value']);

// Get available birds (purchased - ordered - deaths)
$availBirds = $pdo->query("
    SELECT 
      (SELECT IFNULL(SUM(NumberOfBirds),0) FROM birdspurchase)
      - (SELECT IFNULL(SUM(Quantity),0) FROM orders WHERE Type='bird')
      - (SELECT IFNULL(SUM(Deaths),0) FROM birdsmortality)
    AS AvailableBirds
")->fetchColumn();

// Get available eggs (produced - ordered)
$availEggs = $pdo->query("
    SELECT 
      (SELECT IFNULL(SUM(NumberOfEggs),0) FROM production)
      - (SELECT IFNULL(SUM(Quantity),0) FROM orders WHERE Type='egg')
    AS AvailableEggs
")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bird_qty = isset($_POST['bird_qty']) ? intval($_POST['bird_qty']) : 0; // Bird quantity in number of birds
    $egg_qty = isset($_POST['egg_qty']) ? intval($_POST['egg_qty']) : 0;    // Egg quantity in pieces
    $user_id = $_SESSION['User_ID'];
    $success = false;

    // Validate and process bird order
    if ($bird_qty > 0) {
        if ($bird_qty <= $availBirds) {
            $total_bird = $bird_qty * $bird_price_per_bird;
            $pdo->prepare("INSERT INTO orders (User_ID, Type, Item_ID, Quantity, TotalPrice, OrderDate) VALUES (?, 'bird', 0, ?, ?, NOW())")
                ->execute([$user_id, $bird_qty, $total_bird]);
            $availBirds -= $bird_qty;
            $message .= "Ordered {$bird_qty} bird(s) for ₹" . number_format($total_bird,2) . ".<br>";
            $success = true;
        } else {
            $message .= "Not enough birds available.<br>";
        }
    }

    // Validate and process egg order
    if ($egg_qty > 0) {
        if ($egg_qty <= $availEggs) {
            $total_egg = $egg_qty * $egg_price_per_piece;
            $pdo->prepare("INSERT INTO orders (User_ID, Type, Item_ID, Quantity, TotalPrice, OrderDate) VALUES (?, 'egg', 0, ?, ?, NOW())")
                ->execute([$user_id, $egg_qty, $total_egg]);
            $availEggs -= $egg_qty;
            $message .= "Ordered {$egg_qty} egg(s) for ₹" . number_format($total_egg,2) . ".<br>";
            $success = true;
        } else {
            $message .= "Not enough eggs available.<br>";
        }
    }

    if (!$success) {
        $message = "Please enter a valid quantity and ensure enough stock is available.<br>".$message;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Place Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            min-height: 100vh;
        }
        .order-card {
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.13);
            background: #fff;
            padding: 2rem 2.5rem 2rem 2.5rem;
            margin-top: 3rem;
            margin-bottom: 3rem;
            transition: box-shadow 0.2s;
        }
        .order-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,0.17);
        }
        .back-btn {
            font-size: 1rem;
            font-weight: 500;
            border-radius: 10px;
            padding: 0.5em 1.5em;
            background: #fd9644;
            color: #fff;
            border: none;
            margin-top: 1em;
            box-shadow: 0 2px 8px rgba(253,150,68,0.10);
            transition: background 0.2s, color 0.2s;
            text-decoration: none;
        }
        .back-btn:hover {
            background: #fa8231;
            color: #fff;
        }
        .order-btn {
            font-size: 1rem;
            font-weight: 500;
            border-radius: 10px;
            padding: 0.5em 2em;
            background: #20bf6b;
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px rgba(32,191,107,0.10);
            transition: background 0.2s, color 0.2s;
        }
        .order-btn:hover {
            background: #0fb96a;
            color: #fff;
        }
        .form-label {
            font-size: 1.1em;
            color: #2d98da;
        }
    </style>
    <script>
        function updateTotal() {
            let bird_qty = parseInt(document.getElementById('bird_qty').value) || 0;
            let egg_qty  = parseInt(document.getElementById('egg_qty').value) || 0;
            let bird_price = <?= $bird_price_per_bird ?>;
            let egg_price  = <?= $egg_price_per_piece ?>;
            let total = (bird_qty * bird_price) + (egg_qty * egg_price);
            document.getElementById('total-display').innerHTML =
                "Total: ₹" + total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        }
    </script>
</head>
<body>
<div class="container">
    <div class="order-card mx-auto" style="max-width:480px;">
        <h2 class="mb-4" style="color:#8854d0;">🛒 Place Order</h2>
        <?php if($message): ?><div class="alert alert-info"><?= $message ?></div><?php endif; ?>
        <form method="post" oninput="updateTotal()">
            <div class="mb-3">
                <label class="form-label">Bird <span class="text-muted" style="font-size:.9em;">(Available: <?= (int)$availBirds ?> birds,  ₹<?= number_format($bird_price_per_bird,2) ?>/bird)</span></label>
                <input type="number" min="0" step="1" max="<?= (int)$availBirds ?>" name="bird_qty" id="bird_qty" class="form-control" placeholder="Number of Birds" value="0">
            </div>
            <div class="mb-3">
                <label class="form-label">Egg <span class="text-muted" style="font-size:.9em;">(Available: <?= (int)$availEggs ?>,  ₹<?= number_format($egg_price_per_piece,2) ?>/piece)</span></label>
                <input type="number" min="0" max="<?= (int)$availEggs ?>" name="egg_qty" id="egg_qty" class="form-control" placeholder="Number of Eggs" value="0">
            </div>
            <div class="mb-3 fw-bold" id="total-display">Total: ₹0.00</div>
            <button type="submit" class="order-btn">Place Order</button>
        </form>
        <a href="user_dashboard.php" class="back-btn d-block text-center mt-4">⬅️ Back to Menu</a>
    </div>
</div>
<script>updateTotal();</script>
</body>
</html>