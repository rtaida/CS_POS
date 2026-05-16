<?php
require_once '../backend/database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

// Get Dashboard Statistics
$stats = [];

// Total Products
$product_query = "SELECT COUNT(*) as total FROM products WHERE dateDeleted IS NULL";
$product_result = mysqli_query($conn, $product_query);
$stats['products'] = mysqli_fetch_assoc($product_result)['total'];
mysqli_free_result($product_result);

// Total Categories
$cat_query = "SELECT COUNT(*) as total FROM categories WHERE dateDeleted IS NULL";
$cat_result = mysqli_query($conn, $cat_query);
$stats['categories'] = mysqli_fetch_assoc($cat_result)['total'];
mysqli_free_result($cat_result);

// Today's Sales
$today = date('Y-m-d');
$sales_query = "SELECT SUM(grandTotal) as total, COUNT(*) as count FROM sales WHERE DATE(saleDate) = '$today' AND dateDeleted IS NULL";
$sales_result = mysqli_query($conn, $sales_query);
$today_sales = mysqli_fetch_assoc($sales_result);
$stats['today_sales'] = $today_sales['total'] ?? 0;
$stats['today_transactions'] = $today_sales['count'] ?? 0;
mysqli_free_result($sales_result);

// Low Stock Products - FIXED: Clear results properly
$lowstock_query = "CALL GetLowStockProducts()";
$lowstock_result = mysqli_query($conn, $lowstock_query);
$stats['low_stock'] = mysqli_num_rows($lowstock_result);
mysqli_free_result($lowstock_result);

// Clear any additional result sets from the stored procedure
while (mysqli_next_result($conn)) {
    if ($result = mysqli_store_result($conn)) {
        mysqli_free_result($result);
    }
}

// Monthly Sales
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$monthly_query = "SELECT SUM(grandTotal) as total FROM sales WHERE DATE(saleDate) BETWEEN '$month_start' AND '$month_end' AND dateDeleted IS NULL";
$monthly_result = mysqli_query($conn, $monthly_query);
$stats['monthly_sales'] = mysqli_fetch_assoc($monthly_result)['total'] ?? 0;
mysqli_free_result($monthly_result);

// Recent Sales
$recent_query = "SELECT s.*, COUNT(si.saleItemID) as item_count 
                 FROM sales s 
                 LEFT JOIN sale_items si ON s.saleID = si.saleID 
                 WHERE s.dateDeleted IS NULL 
                 GROUP BY s.saleID 
                 ORDER BY s.saleDate DESC 
                 LIMIT 5";
$recent_sales = mysqli_query($conn, $recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CS POS - Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
.dashboard-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

.recent-sales-table {
    border-radius: 15px;
    overflow: hidden;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.chart-container {
    background: white;
    border-radius: 20px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 25px;
    color: white;
    margin-bottom: 25px;
}

.welcome-section h2 {
    font-weight: 600;
    margin-bottom: 5px;
}

.main-content {
    margin-left: 280px;
    padding: 20px;
    min-height: 100vh;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    <div class="welcome-section">
        <h2><i class="bi bi-gem"></i> Welcome back, <?php echo $_SESSION['fullName']; ?>!</h2>
        <p class="mb-0 opacity-75">Here's what's happening with your store today.</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $stats['products']; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="card-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number">₱<?php echo number_format($stats['today_sales'], 2); ?></div>
                        <div class="stat-label">Today's Sales</div>
                        <small class="text-muted"><?php echo $stats['today_transactions']; ?> transactions</small>
                    </div>
                    <div class="card-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number">₱<?php echo number_format($stats['monthly_sales'], 2); ?></div>
                        <div class="stat-label">Monthly Sales</div>
                        <small class="text-muted">This month</small>
                    </div>
                    <div class="card-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number <?php echo $stats['low_stock'] > 0 ? 'text-danger' : ''; ?>">
                            <?php echo $stats['low_stock']; ?>
                        </div>
                        <div class="stat-label">Low Stock Items</div>
                        <small class="text-muted">Need reordering</small>
                    </div>
                    <div class="card-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="chart-container">
                <h5 class="mb-3"><i class="bi bi-graph-up"></i> Sales Trend (Last 7 Days)</h5>
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5 class="mb-3"><i class="bi bi-pie-chart"></i> Sales by Payment Method</h5>
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Sales -->
    <div class="chart-container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="bi bi-clock-history"></i> Recent Transactions</h5>
            <a href="sales.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover recent-sales-table">
                <thead class="table-light">
                    <tr>
                        <th>Invoice No.</th>
                        <th>Date & Time</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($recent_sales) > 0): ?>
                        <?php while($sale = mysqli_fetch_assoc($recent_sales)): ?>
                        <tr>
                            <td><strong><?php echo $sale['invoiceNo']; ?></strong></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($sale['saleDate'])); ?></td>
                            <td><?php echo $sale['item_count']; ?> items</td>
                            <td>₱<?php echo number_format($sale['grandTotal'], 2); ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst($sale['paymentMethod']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-completed">Completed</span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No sales yet today</td>
                        </tr>
                    <?php endif; ?>
                    <?php mysqli_free_result($recent_sales); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Sales Trend Chart
$.ajax({
    url: '../backend/routes.php?route=reports',
    method: 'POST',
    data: { getLast7DaysSales: true },
    dataType: 'json',
    success: function(data) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Daily Sales (₱)',
                    data: data.values || [0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { 
                        callbacks: { 
                            label: (ctx) => `₱${ctx.raw.toFixed(2)}` 
                        } 
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        ticks: { 
                            callback: (value) => '₱' + value.toFixed(2) 
                        } 
                    } 
                }
            }
        });
    },
    error: function() {
        console.log('Error loading sales chart');
    }
});

// Payment Method Chart
$.ajax({
    url: '../backend/routes.php?route=reports',
    method: 'POST',
    data: { getPaymentBreakdown: true },
    dataType: 'json',
    success: function(data) {
        const ctx = document.getElementById('paymentChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels || ['Cash', 'GCash', 'Bank Transfer'],
                datasets: [{
                    data: data.values || [0, 0, 0],
                    backgroundColor: ['#667eea', '#28a745', '#17a2b8', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    },
    error: function() {
        console.log('Error loading payment chart');
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>