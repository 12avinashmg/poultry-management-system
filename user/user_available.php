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
    AS AvailableBirds
")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Available Birds & Eggs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include 'includes/user_sidebar.php'; ?>
<div class="container mt-5">
    <h2>Available Stock</h2>
    <table class="table table-bordered w-50">
        <tr>
            <th>Birds</th>
            <th>Eggs</th>
        </tr>
        <tr>
            <td><?= (int)$availBirds ?></td>
            <td><?= (int)$availEggs ?></td>
        </tr>
    </table>
    <a href="user_placeorder.php" class="btn btn-primary mt-3">Order Now</a>
</div>
</body>
</html>