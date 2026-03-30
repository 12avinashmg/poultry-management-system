<?php
session_start();
require_once 'includes/database.php';

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["Password"])) {
        session_regenerate_id(true);
        $_SESSION["User_ID"] = $user["User_ID"];
        $_SESSION["Username"] = $user["Username"];
        $_SESSION["Role"] = $user["Role"];
        if ($user["Role"] === "admin") {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $message = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login | Poultry Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&family=Poppins:wght@400;600&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(120deg, #a8e063, #56ab2f 60%, #1e3c72 100%);
            font-family: 'Poppins', 'Roboto', sans-serif;
            overflow-x: hidden;
        }
        .navbar {
            background: rgba(255,255,255,0.9)!important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .navbar-brand {
            font-family: 'Luckiest Guy', 'Poppins', cursive;
            color: #198754 !important;
            font-size: 2.3rem;
            letter-spacing: 1px;
            text-shadow: 1px 3px 15px #fff, 0 1px 0px #b5e7a0;
        }
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .poultry-card {
            background: rgba(255,255,255,0.7);
            border-radius: 2.5rem;
            box-shadow: 0 12px 32px 0 rgba(30,60,114,0.12), 0 1.5px 8px 0 rgba(25,135,84,0.10);
            border: none;
            padding: 2.5rem 2.3rem 2rem 2.3rem;
            max-width: 450px;
            width: 100%;
            position: relative;
            backdrop-filter: blur(10px);
            animation: fadeInCard 1.1s ease;
        }
        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .poultry-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .poultry-logo img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 2px 8px #1e3c7240;
            object-fit: cover;
        }
        .card-title {
            font-family: 'Luckiest Guy', cursive;
            font-size: 2.2rem;
            letter-spacing: 1px;
            color: #198754;
            margin-bottom: 1.1rem;
            text-align: center;
            text-shadow: 0 2px 6px #a8e06366;
        }
        .form-label {
            font-weight: 600;
            color: #1c3a13;
            font-size: 1.05rem;
        }
        .form-control {
            background: rgba(255,255,255,0.92);
            border-radius: 1.2rem;
            border: 1.5px solid #e8f6e3;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #56ab2f;
            box-shadow: 0 0 0 .18rem rgba(86,171,47,.17);
        }
        .btn-success {
            background: linear-gradient(90deg,#a8e063 0%,#56ab2f 80%);
            border: none;
            font-weight: bold;
            letter-spacing: 1px;
            border-radius: 1.2rem;
            box-shadow: 0 2px 12px #a8e06333;
            transition: background 0.3s, transform 0.15s;
            font-size: 1.11rem;
        }
        .btn-success:hover {
            background: linear-gradient(270deg,#56ab2f 40%,#a8e063 100%);
            transform: translateY(-2px) scale(1.02);
        }
        .alert-warning {
            background: linear-gradient(90deg, #ffe259 0%, #ffa751 100%);
            border-radius: 1.1rem;
            font-size: 1.02rem;
            border: none;
            color: #885d01;
            box-shadow: 0 2px 8px #ffe25944;
        }
        .register-link {
            color: #198754;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.18s;
        }
        .register-link:hover {
            color: #104e2e;
            text-decoration: underline;
        }
        .poultry-footer {
            margin-top: 2.8rem;
            color: #fff;
            text-shadow: 0 1px 6px #2b4f1c;
            font-size: 1.05em;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            opacity: 0.82;
        }
        .poultry-footer .footer-title {
            font-weight: 600;
            color: #bfffa0;
            letter-spacing: 1.5px;
        }
        @media (max-width: 600px) {
            .poultry-card {padding: 1.4rem 0.7rem 1.2rem 0.7rem; border-radius: 1.3rem;}
            .navbar-brand {font-size: 1.5rem;}
            .card-title {font-size: 1.4rem;}
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-dove"></i> Poultry Management</a>
  </div>
</nav>
<div class="login-wrapper">
  <div class="poultry-card">
    <div class="poultry-logo">
      <img src="https://img.icons8.com/color/96/chicken.png" alt="Poultry Logo">
    </div>
    <div class="card-title">Welcome Back!</div>
    <?php if(!empty($message)) echo "<div class='alert alert-warning text-center'>" . htmlspecialchars($message) . "</div>"; ?>
    <form method="post" action="" autocomplete="off">
      <div class="mb-3">
        <label class="form-label" for="username"><i class="fa fa-user"></i> Username</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Enter your username">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password"><i class="fa fa-lock"></i> Password</label>
        <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
      </div>
      <button type="submit" class="btn btn-success w-100 mt-2"><i class="fa fa-sign-in-alt"></i> Login</button>
    </form>
    <p class="mt-3 text-center">
      <span class="text-muted">Don't have an account?</span>
      <a href="register.php" class="register-link">Register Here</a>
    </p>
    <div class="poultry-footer mt-4">
      &copy; 2025 <span class="footer-title">Poultry Management</span>. All rights reserved.<br>
    </div>
  </div>
</div>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>