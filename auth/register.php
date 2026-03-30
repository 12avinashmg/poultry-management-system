<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE Username=?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $message = "Username already taken!";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (Username, Password) VALUES (?, ?)");
        if ($stmt->execute([$username, $hashedPassword])) {
            $message = "Registration successful! <a href='login.php' style='color:#145c07;font-weight:600;'>Login here</a>.";
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register | Poultry Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(120deg,#a8e063 0%,#56ab2f 50%,#1e3c72 100%);
            font-family: 'Poppins','Montserrat',sans-serif;
            overflow-x: hidden;
        }
        .navbar {
            background: rgba(255,255,255,0.93)!important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.09);
        }
        .navbar-brand {
            font-family: 'Montserrat', 'Poppins', cursive;
            color: #198754 !important;
            font-size: 2.1rem;
            font-weight: bold;
            letter-spacing: 2px;
            text-shadow: 0 2px 10px #fff, 0 1px 0px #bfffa0;
        }
        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-form-card {
            background: rgba(255,255,255,0.8);
            border-radius: 2.4rem;
            box-shadow: 0 12px 36px 0 rgba(30,60,114,0.13), 0 3px 12px 0 rgba(25,135,84,0.13);
            max-width: 450px;
            width: 100%;
            padding: 2.4rem 2.3rem 1.8rem 2.3rem;
            border: none;
            position: relative;
            backdrop-filter: blur(12px);
            animation: fadeInCard 1.1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .register-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .register-logo img {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 4px 16px #1e3c7240;
            object-fit: cover;
        }
        .form-title {
            font-family: 'Montserrat',sans-serif;
            font-size: 2rem;
            color: #145c07;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1.8px;
            margin-bottom: 1.1rem;
            text-shadow: 0 2px 9px #c2e59c38;
        }
        .form-label {
            font-weight: 600;
            color: #114b05;
            letter-spacing: 1px;
            font-size: 1.04rem;
        }
        .form-control {
            border-radius: 1.1rem;
            background: rgba(255,255,255,0.92);
            border: 1.6px solid #b0d96f;
            font-size: 1.09em;
            color: #222;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #145c07;
            box-shadow: 0 0 0 .14rem #b7e4b8;
            color: #111;
        }
        .btn-success {
            background: linear-gradient(90deg,#a8e063 0%,#56ab2f 80%);
            border: none;
            font-weight: bold;
            letter-spacing: 1px;
            border-radius: 1.2rem;
            box-shadow: 0 2px 12px #a8e06333;
            transition: background 0.3s, transform 0.13s;
            font-size: 1.11rem;
            color: #fff;
        }
        .btn-success:hover {
            background: linear-gradient(270deg,#56ab2f 40%,#a8e063 100%);
            color: #fff;
            transform: translateY(-2px) scale(1.025);
        }
        .alert-warning, .alert-success {
            border-radius: 0.9rem;
            font-size: 1.03rem;
            border: none;
            box-shadow: 0 2px 8px #ffe25944;
        }
        .alert-warning {
            background: linear-gradient(90deg, #ffe259 0%, #ffa751 100%);
            color: #8f1a1a;
        }
        .alert-success {
            background: linear-gradient(90deg, #a8e063 0%, #56ab2f 100%);
            color: #145c07;
            font-weight: 600;
        }
        .register-link {
            color: #198754;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.18s;
        }
        .register-link:hover {
            color: #0f4829;
            text-decoration: underline;
        }
        .poultry-footer {
            margin-top: 2.4rem;
            color: #fff;
            text-shadow: 0 1px 8px #0a1f07;
            font-size: 1.07em;
            text-align: center;
            font-family: 'Montserrat', 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            opacity: 0.89;
        }
        .poultry-footer .footer-title {
            font-weight: 700;
            color: #baff6b;
            letter-spacing: 1.5px;
            font-family: 'Montserrat', sans-serif;
        }
        @media (max-width: 600px) {
            .register-form-card {padding: 1.3rem 0.5rem 1rem 0.5rem; border-radius: 1.1rem;}
            .navbar-brand {font-size: 1.4rem;}
            .form-title {font-size: 1.2rem;}
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fa-solid fa-dove"></i> My Poultry</a>
  </div>
</nav>
<div class="register-wrapper">
  <div class="register-form-card">
    <div class="register-logo">
      <img src="https://img.icons8.com/color/96/chicken.png" alt="Poultry Logo">
    </div>
    <div class="form-title">Create Your Account</div>
    <?php if(!empty($message)) {
        $isSuccess = strpos($message, 'successful')!==false;
        echo "<div class='alert ".($isSuccess?'alert-success':'alert-warning')." card-message text-center'>" . $message . "</div>";
    } ?>
    <form method="post" action="" autocomplete="off">
      <div class="mb-3">
        <label class="form-label" for="username"><i class="fa fa-user"></i> Username</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Choose a username">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password"><i class="fa fa-lock"></i> Password</label>
        <input type="password" id="password" name="password" class="form-control" required placeholder="Create a password">
      </div>
      <button type="submit" class="btn btn-success w-100 mt-2"><i class="fa fa-user-plus"></i> Register</button>
    </form>
    <p class="mt-3 text-center">
      <span class="text-dark">Already have an account?</span>
      <a href="login.php" class="register-link">Login Here</a>
    </p>
    <div class="poultry-footer mt-4">
      &copy; 2025 <span class="footer-title">Poultry Management</span>. All rights reserved.
    </div>
  </div>
</div>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>