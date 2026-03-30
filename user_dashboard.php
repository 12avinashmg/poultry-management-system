<?php
session_start();
require_once 'includes/database.php';

if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'user') { header('Location: login.php'); exit(); }

// Calculate available eggs and birds
$availEggs = $pdo->query("
    SELECT 
      (SELECT IFNULL(SUM(NumberOfEggs),0) FROM production)
      - (SELECT IFNULL(SUM(Quantity),0) FROM orders WHERE Type='egg')
    AS AvailableEggs
")->fetchColumn();

$availBirds = $pdo->query("
    SELECT 
      (SELECT IFNULL(SUM(NumberOfBirds),0) FROM birdspurchase)
      - (SELECT IFNULL(SUM(Quantity),0) FROM orders WHERE Type='bird')
      - (SELECT IFNULL(SUM(Deaths),0) FROM birdsmortality)
    AS AvailableBirds
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>🐥 User Dashboard | Poultry</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #f6d365 0%, #fda085 100%);
            min-height: 100vh;
            font-family: 'Roboto',sans-serif;
        }
        .dashboard-card {
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            background: #fffbe7;
            padding: 2rem 2.5rem 2rem 2.5rem;
            margin-top: 3rem;
            margin-bottom: 3rem;
            transition: box-shadow 0.2s;
        }
        .dashboard-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,0.18);
        }
        .stat-icon {
            font-size: 2.7rem;
            margin-right: 0.6rem;
        }
        .stat-label {
            font-weight: 500;
            font-size: 1.2rem;
            color: #f6931f;
            letter-spacing: 1px;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2d8659;
        }
        .dashboard-btn {
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 12px;
            padding: 0.8em 2em;
            margin: 0.5em 0.5em 0.5em 0;
            box-shadow: 0 3px 12px rgba(253,160,133,0.12);
            transition: background 0.2s, color 0.2s;
            display: inline-block;
        }
        .dashboard-btn:hover {
            background: #f6931f;
            color: #fff;
        }
        .address-btn {
            background: #3cba92;
            color: #fff;
        }
        .address-btn:hover {
            background: #2d8659;
            color: #fff;
        }
        .welcome {
            font-size: 2.1rem;
            font-weight: 600;
            color: #d35400;
            letter-spacing: 1px;
            margin-top: 0.7em;
        }
        .emoji {
            font-size: 2.5rem;
            margin-right: 0.4em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-card text-center">
            <div class="welcome mb-4">
                <span class="emoji">👋</span>Welcome, <span style="color:#27ae60"><?= htmlspecialchars($_SESSION['Username']); ?></span>!
            </div>
            <div class="row justify-content-center mb-4">
                <div class="col-md-5 mb-3">
                    <div class="d-flex align-items-center justify-content-center p-3 rounded" style="background-color:#ffe6cc;">
                        <span class="stat-icon">🐦</span>
                        <div>
                            <div class="stat-label">Available Birds</div>
                            <div class="stat-value"><?= (int)$availBirds ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 mb-3">
                    <div class="d-flex align-items-center justify-content-center p-3 rounded" style="background-color:#edfcf4;">
                        <span class="stat-icon">🥚</span>
                        <div>
                            <div class="stat-label">Available Eggs</div>
                            <div class="stat-value"><?= (int)$availEggs ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <a href="user_placeorder.php" class="btn btn-warning dashboard-btn">🛒 Place Order</a>
                <a href="user_orders.php" class="btn btn-success dashboard-btn">📦 My Orders</a>
                <a href="user_address.php" class="btn address-btn dashboard-btn">🏠 My Addresses</a>
                <a href="logout.php" class="btn btn-danger dashboard-btn">🚪 Logout</a>
            </div>
        </div>
        <div class="mt-4 text-center" style="font-size:1.3rem; font-weight:600; letter-spacing:1px;">
    <span style="
        color: #181818;
        background: #f6f9ff;
        border-radius: 14px;
        padding: 0.5em 1.2em;
        box-shadow: 0 2px 14px rgba(34,49,63,0.09);
        display: inline-block;
    ">
        <span class="emoji" style="font-size:2.1rem;">🐔</span>
        Welcome to our Poultry Shop!  
        <span class="emoji" style="font-size:1.6rem;">🌟</span>
        <br>
        Explore more, discover fresh choices, and enjoy placing your orders with us.<br>
        <span class="emoji" style="font-size:1.6rem;">🥚🍗🛒</span>
        <br>
        <span style="font-size:1.04rem; color:#2980b9;">Thank you for being a part of our poultry family!</span>
    </span>
</div>
    </div>
</body>
</html>