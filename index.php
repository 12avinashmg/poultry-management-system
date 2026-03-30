<?php
session_start();

if (isset($_SESSION['admin'])) {
    header("Location: admin/admin_dashboard.php");
} else {
    header("Location: auth/login.php");
}
exit();
?>