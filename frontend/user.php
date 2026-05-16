<?php
require_once '../backend/database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

$csrf_token = $_SESSION['csrf_token'];

$userCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE dateDeleted IS NULL"))['total'];
$adminCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE userID = 1 AND dateDeleted IS NULL"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
<title>CS POS - User Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Pusher JS -->
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f0f2f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.main-content {
    margin-left: 280px;
    padding: 25px 30px;
    min-height: 100vh;
    background: #f5f7fb;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

/* Header Section */
.page-header {
    background: white;
    border-radius: 24px;
    padding: 25px 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    border: 1px solid rgba(0,0,0,0.05);
}

.page-header h3 {
    font-weight: 700;
    font-size: 28px;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #1a1a2e 0%, #667eea 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header p {
    color: #6c757d;
    font-size: 14px;
    margin: 0;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    margin-bottom: 35px;
}

.stats-card {
    background: white;
    border-radius: 20px;
    padding: 22px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.stats-icon {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}

.stats-icon i {
    font-size: 28px;
    color: #667eea;
}

.stats-number {
    font-size: 36px;
    font-weight: 800;
    color: #1a1a2e;
    line-height: 1.2;
    margin-bottom: 5px;
}

.stats-label {
    color: #6c757d;
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Button Styles */
.btn-add {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 14px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(102,126,234,0.3);
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.4);
    color: white;
}

.btn-edit {
    background: #fff3e0;
    color: #ff9800;
    border: none;
    padding: 8px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-edit:hover {
    background: #ff9800;
    color: white;
    transform: scale(1.02);
}

.btn-delete {
    background: #ffe8e8;
    color: #f44336;
    border: none;
    padding: 8px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-delete:hover {
    background: #f44336;
    color: white;
    transform: scale(1.02);
}

/* User Grid */
.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
}

.user-card {
    background: white;
    border-radius: 24px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.05);
    overflow: hidden;
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.user-card-header {
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.user-avatar {
    width: 65px;
    height: 65px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(102,126,234,0.3);
}

.user-avatar i {
    font-size: 32px;
    color: white;
}

.user-info-header {
    flex: 1;
}

.user-name {
    font-weight: 700;
    font-size: 18px;
    color: #1a1a2e;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.user-badge {
    font-size: 10px;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.badge-admin {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.badge-you {
    background: #28a745;
    color: white;
}

.user-username {
    font-size: 12px;
    color: #667eea;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-email {
    font-size: 12px;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-card-body {
    padding: 20px;
}

.user-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.user-joined {
    font-size: 11px;
    color: #999;
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-role {
    background: #e8f0fe;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    color: #667eea;
    font-weight: 600;
}

.card-actions {
    display: flex;
    gap: 12px;
}

/* Modal Styles */
.modern-modal .modal-content {
    border-radius: 24px;
    border: none;
    overflow: hidden;
}

.modern-modal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    border: none;
}

.modern-modal .modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.modern-modal .modal-header .btn-close:hover {
    opacity: 1;
}

.modern-modal .modal-body {
    padding: 25px;
}

.modern-modal .modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
}

.modern-modal .form-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 13px;
}

.modern-modal .form-label i {
    color: #667eea;
    margin-right: 5px;
}

.modern-modal .form-control {
    border-radius: 12px;
    border: 2px solid #e8eef2;
    padding: 12px 15px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.modern-modal .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 24px;
}

.empty-state i {
    font-size: 70px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h5 {
    font-size: 20px;
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
}

/* Pusher notification styles */
.pusher-floating-badge {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.pusher-floating-badge:hover {
    transform: scale(1.1);
}

/* Animation */
@keyframes bounceIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.swal2-toast {
    animation: slideInRight 0.3s ease !important;
    border-radius: 12px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .user-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        padding: 20px;
    }
    
    .page-header h3 {
        font-size: 22px;
    }
    
    .btn-add {
        padding: 10px 20px;
        font-size: 13px;
    }
    
    .card-actions {
        flex-direction: column;
    }
    
    .btn-edit, .btn-delete {
        justify-content: center;
    }
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    <!-- Header with Add Button -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3><i class="bi bi-people-fill"></i> User Management</h3>
                <p>Manage system users, their roles, and account settings</p>
            </div>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-lg"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stats-number"><?php echo $userCount; ?></div>
            <div class="stats-label">Total Users</div>
        </div>
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="stats-number">
                <?php 
                $activeCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE dateDeleted IS NULL"))['total'];
                echo $activeCount;
                ?>
            </div>
            <div class="stats-label">Active Accounts</div>
        </div>
        <div class="stats-card">
            <div class="stats-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="stats-number"><?php echo $adminCount; ?></div>
            <div class="stats-label">Administrators</div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if(isset($_GET['savedData'])): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'User Added!',
        text: 'New user has been created successfully.',
        timer: 2500,
        showConfirmButton: false
    });
    </script>
    <?php endif; ?>

    <?php if(isset($_GET['updateData'])): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'User Updated!',
        text: 'User information has been updated.',
        timer: 2500,
        showConfirmButton: false
    });
    </script>
    <?php endif; ?>

    <?php if(isset($_GET['deleteData'])): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'User Deleted!',
        text: 'User has been removed from the system.',
        timer: 2500,
        showConfirmButton: false
    });
    </script>
    <?php endif; ?>

    <?php if(isset($_GET['already'])): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Duplicate Entry!',
        text: 'Username or email already exists. Please use different credentials.',
        confirmButtonColor: '#dc3545'
    });
    </script>
    <?php endif; ?>

    <!-- User Grid -->
    <div class="user-grid">
        <?php
        $result = mysqli_query($conn, "SELECT * FROM users WHERE dateDeleted IS NULL ORDER BY userID ASC");
        if(mysqli_num_rows($result) > 0):
            while($row = mysqli_fetch_assoc($result)):
                $isCurrentUser = ($row['userID'] == $_SESSION['userID']);
                $isAdmin = ($row['userID'] == 1);
        ?>
        <div class="user-card" data-user-id="<?php echo $row['userID']; ?>">
            <div class="user-card-header">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="user-info-header">
                    <div class="user-name">
                        <?php echo htmlspecialchars($row['fullName']); ?>
                        <?php if($isAdmin): ?>
                            <span class="user-badge badge-admin"><i class="bi bi-shield-check"></i> Admin</span>
                        <?php endif; ?>
                        <?php if($isCurrentUser): ?>
                            <span class="user-badge badge-you"><i class="bi bi-person-check"></i> You</span>
                        <?php endif; ?>
                    </div>
                    <div class="user-username">
                        <i class="bi bi-at"></i> <?php echo htmlspecialchars($row['username']); ?>
                    </div>
                    <div class="user-email">
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
                    </div>
                </div>
            </div>
            <div class="user-card-body">
                <div class="user-meta">
                    <div class="user-joined">
                        <i class="bi bi-calendar3"></i>
                        Joined: <?php echo date('M d, Y', strtotime($row['created_at'] ?? date('Y-m-d'))); ?>
                    </div>
                    <div class="user-role">
                        <i class="bi bi-person-badge"></i> <?php echo $isAdmin ? 'Full Access' : 'Staff Access'; ?>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $row['userID']; ?>">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <?php if(!$isCurrentUser): ?>
                    <button class="btn-delete" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $row['userID']; ?>">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade modern-modal" id="editUserModal<?php echo $row['userID']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="../backend/userAuth.php">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="userID" value="<?php echo $row['userID']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-person"></i> Full Name</label>
                                <input type="text" name="fullName" class="form-control" value="<?php echo htmlspecialchars($row['fullName']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-at"></i> Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="updateUser" class="btn btn-warning">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade modern-modal" id="deleteUserModal<?php echo $row['userID']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="../backend/userAuth.php">
                        <div class="modal-header" style="background: #dc3545;">
                            <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Delete User</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="userID" value="<?php echo $row['userID']; ?>">
                            <div class="text-center py-4">
                                <i class="bi bi-trash3" style="font-size: 55px; color: #dc3545;"></i>
                                <h5 class="mt-3 fw-bold">Confirm Deletion</h5>
                                <p class="mb-2">Are you sure you want to delete <strong><?php echo htmlspecialchars($row['fullName']); ?></strong>?</p>
                                <p class="text-muted small mb-0">This action cannot be undone.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="DeleteUser" class="btn btn-danger">Yes, Delete User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <h5>No Users Found</h5>
            <p>Click the "Add New User" button to create your first user.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade modern-modal" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="../backend/userAuth.php" id="addUserForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill"></i> Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person"></i> Full Name</label>
                        <input type="text" name="fullName" class="form-control" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-at"></i> Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="Choose a username">
                        <small class="text-muted">Used for login</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope"></i> Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-lock"></i> Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Create a password" minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="userAuth" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<script>

Pusher.logToConsole = true;

var pusher = new Pusher('3d5e91994ffcfa8ec0b5', {
    cluster: 'ap1'
});

var channel = pusher.subscribe('my-channel');
channel.bind('my-event', function(data) {
    console.log('Pusher event received:', data);
    
   
    switch(data.action) {
        case 'add':
           
            Swal.fire({
                icon: 'success',
                title: '🆕 New User Added!',
                html: `
                    <div style="text-align: left;">
                        <strong>${data.fullName}</strong> has been added to the system.<br>
                        <small>Username: ${data.username}</small><br>
                        <small>Email: ${data.email}</small><br>
                        <small class="text-muted">Added by: ${data.triggered_by}</small>
                    </div>
                `,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                background: '#28a745',
                color: 'white'
            });
            break;
            
        case 'update':
          
            let changesHtml = '';
            if(data.changes && data.changes.length > 0) {
                changesHtml = '<div class="mt-2"><strong>Changes:</strong><ul style="margin: 5px 0 0 15px;">';
                data.changes.forEach(change => {
                    changesHtml += `<li style="font-size: 11px;">${change}</li>`;
                });
                changesHtml += '</ul></div>';
            }
            
            Swal.fire({
                icon: 'info',
                title: '✏️ User Updated',
                html: `
                    <div style="text-align: left;">
                        <strong>${data.fullName}</strong>'s information has been updated.<br>
                        <small>Username: ${data.username}</small><br>
                        <small>Email: ${data.email}</small><br>
                        <small class="text-muted">Updated by: ${data.changed_by}</small>
                        ${changesHtml}
                    </div>
                `,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                background: '#ff9800',
                color: 'white'
            });
            break;
            
        case 'delete':
          
            Swal.fire({
                icon: 'warning',
                title: '🗑️ User Deleted',
                html: `
                    <div style="text-align: left;">
                        <strong>${data.fullName}</strong> has been removed from the system.<br>
                        <small>Username: ${data.username}</small><br>
                        <small>Email: ${data.email}</small><br>
                        <small class="text-muted">Deleted by: ${data.deleted_by}</small>
                    </div>
                `,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                background: '#dc3545',
                color: 'white'
            });
            break;
            
        default:
         
            Swal.fire({
                icon: 'info',
                title: '🔔 Notification',
                html: `<strong>${data.fullName}</strong><br>${data.message}`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                background: 'linear-gradient(135deg, #667eea, #764ba2)',
                color: 'white'
            });
            break;
    }
    
    
    if(data.action === 'add' || data.action === 'delete') {
        setTimeout(function() {
            location.reload();
        }, 3000);
    } else if(data.action === 'update') {
        setTimeout(function() {
            location.reload();
        }, 3000);
    }
    
    
    showFloatingBadge(data.action);
});

function showFloatingBadge(action) {
    let badge = document.querySelector('.pusher-floating-badge');
    if(badge) {
        badge.remove();
    }
    
    let newBadge = document.createElement('div');
    newBadge.className = 'pusher-floating-badge';
    
    let icon = '';
    let bgColor = '';
    switch(action) {
        case 'add':
            icon = '✓';
            bgColor = '#28a745';
            break;
        case 'update':
            icon = '✎';
            bgColor = '#ff9800';
            break;
        case 'delete':
            icon = '✗';
            bgColor = '#dc3545';
            break;
        default:
            icon = '🔔';
            bgColor = '#667eea';
    }
    
    newBadge.innerHTML = icon;
    newBadge.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        z-index: 9999;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        animation: bounceIn 0.5s ease;
        transition: transform 0.3s ease;
    `;
    
    newBadge.onclick = function() {
        this.remove();
    };
    
    document.body.appendChild(newBadge);
    
    setTimeout(() => {
        if(newBadge) newBadge.remove();
    }, 3000);
}


if(window.location.search.length > 0 && !window.location.search.includes('?')) {
    setTimeout(() => {
        window.history.replaceState({}, document.title, window.location.pathname);
    }, 3000);
}


document.getElementById('addUserForm')?.addEventListener('submit', function(e) {
    let password = this.querySelector('input[name="password"]').value;
    if(password.length < 6) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Password Too Short',
            text: 'Password must be at least 6 characters long.',
            confirmButtonColor: '#dc3545'
        });
    }
});
</script>

</body>
</html>