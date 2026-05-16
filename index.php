<?php
session_start();

if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

if(isset($_SESSION['userID'])){
    header("Location: ./frontend/pos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CS POS - Sari-sari Store POS System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
}

.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-card {
    background: rgba(255,255,255,0.95);
    border-radius: 30px;
    padding: 40px;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease;
}

.login-card:hover {
    transform: translateY(-5px);
}

.logo-section {
    text-align: center;
    margin-bottom: 30px;
}

.logo-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.logo-icon i {
    font-size: 40px;
    color: white;
}

.logo-section h2 {
    font-weight: 700;
    margin-bottom: 5px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.logo-section p {
    color: #666;
    font-size: 14px;
}

.input-group-custom {
    margin-bottom: 20px;
}

.input-group-custom label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.input-group-custom input {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 15px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.input-group-custom input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.login-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 15px;
    color: white;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(102,126,234,0.4);
}

.demo-info {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    font-size: 12px;
    color: #999;
}

.demo-info strong {
    color: #667eea;
}

.alert-custom {
    border-radius: 15px;
    margin-bottom: 20px;
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="bi bi-shop"></i>
            </div>
            <h2>CS POS</h2>
            <p>Sari-sari Store Management System</p>
        </div>

        <?php if(isset($_GET['invalid'])): ?>
        <div class="alert alert-danger alert-custom">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Invalid email or password. Please try again.
        </div>
        <?php endif; ?>

        <form action="./backend/routes.php?route=login" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="input-group-custom">
                <label><i class="bi bi-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="input-group-custom">
                <label><i class="bi bi-lock"></i> Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" name="loginAuth" class="login-btn">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>
        
        <div class="demo-info">
            <p><strong>Demo Credentials:</strong></p>
            <p>Email: admin@cspos.com | Password: password</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>