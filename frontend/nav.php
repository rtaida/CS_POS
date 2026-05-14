<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="bi bi-shop"></i> CS POS</h3>
        <p class="text-white-50">Sari-sari Store System</p>
    </div>
    
    <div class="sidebar-menu">
        <a href="pos.php" class="menu-item">
            <i class="bi bi-cart"></i> Point of Sale
        </a>
        <a href="dashboard.php" class="menu-item">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="products.php" class="menu-item">
            <i class="bi bi-box"></i> Products
        </a>
        <a href="categories.php" class="menu-item">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="sales.php" class="menu-item">
            <i class="bi bi-receipt"></i> Sales History
        </a>
        <a href="reports.php" class="menu-item">
            <i class="bi bi-graph-up"></i> Reports
        </a>
        <!-- User Management Section -->
        <a href="user.php" class="menu-item">
            <i class="bi bi-people"></i> Users
        </a>
    </div>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="bi bi-person-circle"></i>
            <span><?php echo $_SESSION['fullName'] ?? 'User'; ?></span>
        </div>
        <a href="#" onclick="event.preventDefault(); confirmLogout();" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<script>
function confirmLogout() {
    Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, Logout'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}

// Highlight active menu item based on current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href === currentPage) {
            item.classList.add('active');
        }
    });
});
</script>

<style>
.sidebar {
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    position: fixed;
    left: 0;
    top: 0;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 20px rgba(0,0,0,0.1);
    z-index: 1000;
}

.sidebar-header {
    padding: 25px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    font-weight: 600;
    margin-bottom: 5px;
}

.sidebar-header p {
    font-size: 12px;
    margin: 0;
}

.sidebar-menu {
    flex: 1;
    padding: 20px 15px;
    overflow-y: auto;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    margin: 5px 0;
    color: #e0e0e0;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.menu-item i {
    font-size: 1.2rem;
    width: 24px;
}

.menu-item:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    transform: translateX(5px);
}

.menu-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(102,126,234,0.3);
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    margin-bottom: 15px;
}

.user-info i {
    font-size: 1.5rem;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    color: #ff6b6b;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background: rgba(255,107,107,0.1);
    color: #ff8e8e;
}

.main-content {
    margin-left: 280px;
    padding: 20px;
    background: #f5f7fb;
    min-height: 100vh;
}

/* Scrollbar styling */
.sidebar-menu::-webkit-scrollbar {
    width: 5px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.3);
    border-radius: 10px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.5);
}

/* Responsive */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .menu-toggle {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: #667eea;
        border: none;
        color: white;
        padding: 10px 15px;
        border-radius: 10px;
        cursor: pointer;
    }
}
</style>

<!-- Mobile menu toggle button (hidden on desktop) -->
<button class="menu-toggle d-lg-none" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<script>
// Mobile sidebar toggle function
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.menu-toggle');
    
    if (window.innerWidth <= 992) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('open');
        }
    }
});
</script>