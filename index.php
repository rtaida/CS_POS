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
<title>CS POS - Point of Sale System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow-x: hidden;
}


.circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    pointer-events: none;
}

.circle-1 {
    width: 250px;
    height: 250px;
    top: -125px;
    right: -125px;
}

.circle-2 {
    width: 400px;
    height: 400px;
    bottom: -200px;
    left: -200px;
}

.circle-3 {
    width: 150px;
    height: 150px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.container-custom {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-wrapper {
    background: white;
    border-radius: 32px;
    display: flex;
    overflow: hidden;
    max-width: 950px;
    width: 100%;
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.25);
}


.login-illustration {
    flex: 1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 35px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.illustration-content {
    position: relative;
    z-index: 2;
    color: white;
}

.illustration-content .logo {
    margin-bottom: 25px;
}

.illustration-content .logo i {
    font-size: 38px;
    background: rgba(255, 255, 255, 0.2);
    padding: 12px;
    border-radius: 16px;
    margin-bottom: 15px;
}

.illustration-content h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}

.illustration-content p {
    font-size: 13px;
    opacity: 0.9;
    line-height: 1.5;
}

.feature-list {
    margin-top: 20px;
    list-style: none;
    padding: 0;
}

.feature-list li {
    margin-bottom: 8px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.feature-list li i {
    font-size: 14px;
    opacity: 0.9;
}

.illustration-image {
    position: relative;
    z-index: 2;
    text-align: center;
    margin-top: 20px;
}

.illustration-image .main-icon {
    font-size: 100px;
    opacity: 0.2;
    color: white;
    position: relative;
    display: inline-block;
}

.illustration-image .overlay-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 45px;
    color: white;
    opacity: 0.8;
}


.dots {
    position: absolute;
    bottom: 20px;
    right: 20px;
    z-index: 1;
}

.dot {
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    position: absolute;
}

.dot-1 {
    width: 60px;
    height: 60px;
    bottom: 15px;
    right: 15px;
}

.dot-2 {
    width: 90px;
    height: 90px;
    bottom: -25px;
    right: -25px;
}


.login-form-container {
    flex: 1;
    padding: 40px 35px;
    background: white;
}

.login-header {
    margin-bottom: 25px;
}

.login-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 6px;
}

.login-header p {
    color: #718096;
    font-size: 13px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #2d3748;
    font-size: 13px;
}

.form-group label i {
    margin-right: 6px;
    color: #667eea;
    font-size: 12px;
}

.form-group input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
}

.form-group input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input::placeholder {
    color: #a0aec0;
    font-size: 12px;
}


.checkbox-group {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 12px;
    color: #4a5568;
}

.checkbox-label input {
    width: auto;
    margin-right: 6px;
    cursor: pointer;
    accent-color: #667eea;
}

.forgot-link {
    color: #667eea;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.forgot-link:hover {
    color: #764ba2;
    text-decoration: underline;
}

.login-button {
    width: 100%;
    padding: 11px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    cursor: pointer;
    margin-bottom: 20px;
}

.login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px -5px rgba(102, 126, 234, 0.4);
}

.login-button:active {
    transform: translateY(0);
}

.login-button i {
    margin-right: 6px;
}

.divider {
    text-align: center;
    position: relative;
    margin: 20px 0;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e2e8f0;
}

.divider span {
    background: white;
    padding: 0 12px;
    position: relative;
    color: #a0aec0;
    font-size: 11px;
    text-transform: uppercase;
}

.presented-by {
    text-align: center;
    margin-top: 25px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}

.presented-by p {
    font-size: 10px;
    color: #a0aec0;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.presented-by p i {
    color: #667eea;
}

.presented-by .security-badge {
    font-size: 9px;
    color: #cbd5e0;
}

.alert-custom {
    border-radius: 10px;
    margin-bottom: 18px;
    border: none;
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 500;
    animation: shake 0.5s ease-in-out;
}

.alert-custom.alert-danger {
    background: #fed7d7;
    color: #c53030;
}

.alert-custom.alert-success {
    background: #c6f6d5;
    color: #22543d;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Modal styling */
.modal-content {
    border-radius: 20px;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
}

.modal-header .btn-close-white {
    filter: brightness(0) invert(1);
}

.modal-body {
    padding: 25px;
}

.modal-body i {
    font-size: 50px;
    color: #667eea;
}


@media (max-width: 850px) {
    .login-illustration {
        display: none;
    }
    
    .login-form-container {
        flex: none;
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }
    
    .login-wrapper {
        max-width: 400px;
    }
}

@media (max-width: 480px) {
    .login-form-container {
        padding: 30px 25px;
    }
    
    .login-header h2 {
        font-size: 24px;
    }
}
</style>
</head>
<body>

<div class="circle circle-1"></div>
<div class="circle circle-2"></div>
<div class="circle circle-3"></div>

<div class="container-custom">
    <div class="login-wrapper">

        <div class="login-illustration">
            <div class="illustration-content">
                <div class="logo">
                    <i class="bi bi-cart-check-fill"></i>
                    <h1><br>JSJC SARI-SARI STORE</h1>
                    <p>Point of Sale System</p>
                </div>
                
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle-fill"></i> Inventory Management</li>
                    <li><i class="bi bi-check-circle-fill"></i> Adhoc Reporting</li>
                    <li><i class="bi bi-check-circle-fill"></i> User Management</li>
                    <li><i class="bi bi-check-circle-fill"></i> Real-time Reporting</li>
                </ul>
            </div>
            
            <div class="illustration-image">
                <i class="bi bi-receipt main-icon"></i>
                <i class="bi bi-upc-scan overlay-icon"></i>
            </div>
            
            <div class="dots">
                <div class="dot dot-1"></div>
                <div class="dot dot-2"></div>
            </div>
        </div>
        
   
        <div class="login-form-container">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please login to your account to continue</p>
            </div>
            
            <?php if(isset($_GET['invalid'])): ?>
            <div class="alert alert-danger alert-custom">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Invalid credentials. Please check your email and password.
            </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['logout'])): ?>
            <div class="alert alert-success alert-custom">
                <i class="bi bi-check-circle-fill me-2"></i>
                You have been successfully logged out.
            </div>
            <?php endif; ?>
            
            <form action="./backend/auth.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label><i class="bi bi-envelope-fill"></i> Email Address</label>
                    <input type="email" name="email" placeholder="admin@example.com" required autocomplete="email" autofocus>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-key-fill"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                </div>
                
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="keep_logged_in"> Keep me logged in
                    </label>
                    <a href="#" class="forgot-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                </div>
                
                <button type="submit" name="loginAuth" class="login-button">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>
            
            <div class="divider">
                <span><i class="bi bi-shield-check"></i> Secure Point of Sale System</span>
            </div>
            
            <div class="presented-by">
                <p><i class="bi bi-credit-card"></i> presented by <br>Jared<br>Jaybart<br>Switt<br>CJ</p>
                <p class="security-badge">
                    
                </p>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="forgotPasswordModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                <h5 class="modal-title"><i class="bi bi-key"></i> Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-envelope-paper" style="font-size: 50px; color: #667eea;"></i>
                    <h5 class="mt-2 mb-2">Forgot Password?</h5>
                    <p class="text-muted small">Enter your email to receive reset instructions.</p>
                </div>
                <form id="forgotPasswordForm">
                    <div class="form-group mb-3">
                        <label class="small"><i class="bi bi-envelope"></i> Email Address</label>
                        <input type="email" class="form-control form-control-sm" id="resetEmail" placeholder="admin@example.com" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px; border-radius: 10px; font-size: 13px;">
                        <i class="bi bi-send"></i> Send Reset Link
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

const form = document.querySelector('form');
if(form) {
    form.addEventListener('submit', function(e) {
        const button = form.querySelector('.login-button');
        if(button && !button.disabled) {
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Signing in...';
            button.disabled = true;
        }
    });
}


const forgotForm = document.getElementById('forgotPasswordForm');
if(forgotForm) {
    forgotForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('resetEmail').value;
        const button = forgotForm.querySelector('button');
        
        if(email) {
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
            button.disabled = true;
            
            
            setTimeout(() => {
                Swal.fire({
                    title: 'Reset Link Sent!',
                    text: `Password reset instructions have been sent to ${email}`,
                    icon: 'success',
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'small-swal'
                    }
                }).then(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
                    modal.hide();
                    button.innerHTML = '<i class="bi bi-send"></i> Send Reset Link';
                    button.disabled = false;
                    document.getElementById('resetEmail').value = '';
                });
            }, 1500);
        }
    });
}
</script>

<style>

.small-swal {
    font-size: 13px;
    width: 350px;
    padding: 15px;
}
</style>
</body>
</html>