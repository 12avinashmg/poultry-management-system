<nav class="nav flex-column w-100 px-3 mb-4">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='user_available.php'?'active':'' ?>" href="user_available.php">🐦 Available Stock</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='user_placeorder.php'?'active':'' ?>" href="user_placeorder.php">🛒 Place Order</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='user_orders.php'?'active':'' ?>" href="user_orders.php">📦 My Orders</a>
    <a class="nav-link" href="logout.php">🚪 Logout</a>
</nav>