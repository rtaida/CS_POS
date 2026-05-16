<?php
require_once '../backend/database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

// Handle delete request
if(isset($_POST['deleteSale']) && isset($_POST['saleID'])) {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: sales.php?error=invalid_token");
        exit();
    }
    
    $saleID = mysqli_real_escape_string($conn, $_POST['saleID']);
    $dateDeleted = date('Y-m-d H:i:s');
    
    // Soft delete the sale
    $query = "UPDATE sales SET dateDeleted = '$dateDeleted' WHERE saleID = '$saleID'";
    
    if(mysqli_query($conn, $query)){
        header("Location: sales.php?deleted=true");
        exit();
    } else {
        header("Location: sales.php?error=delete_failed");
        exit();
    }
}

// Get filter values from GET parameters
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Set dates based on filter
$currentDate = date('Y-m-d');
switch($dateFilter){
    case 'today':
        $startDate = $currentDate;
        $endDate = $currentDate;
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = $currentDate;
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'custom':
        // Use the provided start_date and end_date
        if(empty($startDate)) $startDate = $currentDate;
        if(empty($endDate)) $endDate = $currentDate;
        break;
    default:
        $startDate = $currentDate;
        $endDate = $currentDate;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereClause = "s.dateDeleted IS NULL";
$whereClause .= " AND DATE(s.saleDate) BETWEEN '$startDate' AND '$endDate'";

// Get total count for pagination
$totalQuery = "SELECT COUNT(DISTINCT s.saleID) as total 
               FROM sales s 
               WHERE $whereClause";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalSales = $totalRow['total'] ?? 0;
$totalPages = $totalPages = ($totalSales > 0) ? ceil($totalSales / $limit) : 1;

// Get sales data
$query = "SELECT s.*, COUNT(si.saleItemID) as item_count 
          FROM sales s 
          LEFT JOIN sale_items si ON s.saleID = si.saleID 
          WHERE $whereClause 
          GROUP BY s.saleID 
          ORDER BY s.saleDate DESC 
          LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// Get summary for current filter
$summaryQuery = "SELECT 
                    COALESCE(SUM(s.grandTotal), 0) as total, 
                    COUNT(s.saleID) as count, 
                    COALESCE(AVG(s.grandTotal), 0) as avg 
                 FROM sales s 
                 WHERE $whereClause";
$summaryResult = mysqli_query($conn, $summaryQuery);
$summary = mysqli_fetch_assoc($summaryResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CS POS - Sales History</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
.sales-header {
    background: white;
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-bar {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.sale-card {
    background: white;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.sale-card:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.invoice-number {
    font-weight: 700;
    font-size: 16px;
    color: #667eea;
}

.receipt-modal .modal-content {
    border-radius: 20px;
}

.receipt-header {
    text-align: center;
    border-bottom: 2px dashed #ddd;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.receipt-items {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.receipt-total {
    border-top: 2px dashed #ddd;
    margin-top: 15px;
    padding-top: 15px;
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

.summary-card {
    background: white;
    border-radius: 15px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-align: center;
}

.summary-card h4 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.summary-card small {
    color: #666;
    font-size: 12px;
}

.btn-delete {
    background: #dc3545;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 12px;
    transition: all 0.3s ease;
}

.btn-delete:hover {
    background: #c82333;
    transform: scale(1.05);
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.date-range-display {
    background: #e8f0fe;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.date-range-display span {
    font-weight: 600;
    color: #667eea;
}

.filter-label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #555;
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    <div class="sales-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><i class="bi bi-receipt"></i> Sales History</h3>
                <p class="text-muted mb-0">View and manage all transactions</p>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if(isset($_GET['deleted'])): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: 'Sale record has been deleted successfully.'
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
    </script>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: 'Failed to delete sale record.'
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
    </script>
    <?php endif; ?>
    
    <!-- Date Range Display -->
    <div class="date-range-display">
        <i class="bi bi-calendar-range"></i> 
        Showing sales from <span><?php echo date('F d, Y', strtotime($startDate)); ?></span> 
        to <span><?php echo date('F d, Y', strtotime($endDate)); ?></span>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="filter-label">Quick Filter</label>
                <select name="date_filter" class="form-select" onchange="this.form.submit()">
                    <option value="today" <?php echo $dateFilter == 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo $dateFilter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="week" <?php echo $dateFilter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $dateFilter == 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="custom" <?php echo $dateFilter == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="filter-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-3">
                <label class="filter-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100" name="apply_filter" value="1">
                    <i class="bi bi-funnel"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Sales Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="summary-card">
                <small><i class="bi bi-cash-stack"></i> Total Sales</small>
                <h4 class="text-success">₱<?php echo number_format($summary['total'] ?? 0, 2); ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card">
                <small><i class="bi bi-receipt"></i> Transactions</small>
                <h4><?php echo $summary['count'] ?? 0; ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="summary-card">
                <small><i class="bi bi-graph-up"></i> Average Transaction</small>
                <h4>₱<?php echo number_format($summary['avg'] ?? 0, 2); ?></h4>
            </div>
        </div>
    </div>
    
    <!-- Sales List -->
    <?php if(isset($result) && mysqli_num_rows($result) > 0): ?>
        <?php while($sale = mysqli_fetch_assoc($result)): ?>
        <div class="sale-card" id="sale-<?php echo $sale['saleID']; ?>">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <div class="invoice-number">#<?php echo $sale['invoiceNo']; ?></div>
                    <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($sale['saleDate'])); ?></small>
                </div>
                <div class="col-md-2">
                    <span class="badge bg-secondary"><?php echo ucfirst($sale['paymentMethod']); ?></span>
                    <?php if(!empty($sale['customerName'])): ?>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($sale['customerName']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-2">
                    <div><i class="bi bi-box"></i> <?php echo $sale['item_count']; ?> items</div>
                </div>
                <div class="col-md-2">
                    <div class="fw-bold text-success">₱<?php echo number_format($sale['grandTotal'], 2); ?></div>
                    <?php if($sale['discount'] > 0): ?>
                    <small class="text-danger">Discount: -₱<?php echo number_format($sale['discount'], 2); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewReceipt(<?php echo $sale['saleID']; ?>)">
                            <i class="bi bi-receipt"></i> View
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="printReceipt(<?php echo $sale['saleID']; ?>)">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button class="btn btn-sm btn-delete" onclick="confirmDelete(<?php echo $sale['saleID']; ?>, '<?php echo addslashes($sale['invoiceNo']); ?>')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No sales found for the selected period.
        </div>
    <?php endif; ?>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&date_filter=<?php echo $dateFilter; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="bi bi-chevron-left"></i> Previous
                </a>
            </li>
            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for($i = $startPage; $i <= $endPage; $i++): 
            ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&date_filter=<?php echo $dateFilter; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&date_filter=<?php echo $dateFilter; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    Next <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="saleID" id="deleteSaleID">
    <input type="hidden" name="deleteSale" value="1">
</form>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content receipt-modal">
            <div class="modal-body" id="receiptContent">
                <!-- Receipt content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(saleID, invoiceNo) {
    Swal.fire({
        title: 'Delete Sale?',
        html: `Are you sure you want to delete sale <strong>${invoiceNo}</strong>?<br><br>
               <span class="text-danger">This action cannot be undone!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteSaleID').value = saleID;
            document.getElementById('deleteForm').submit();
        }
    });
}

function viewReceipt(saleID) {
    $.ajax({
        url: '../backend/routes.php?route=reports',
        method: 'POST',
        data: { getReceipt: true, saleID: saleID },
        success: function(response) {
            $('#receiptContent').html(response);
            new bootstrap.Modal(document.getElementById('receiptModal')).show();
        },
        error: function() {
            Swal.fire('Error', 'Could not load receipt', 'error');
        }
    });
}

function printReceipt(saleID) {
    $.ajax({
        url: '../backend/routes.php?route=reports',
        method: 'POST',
        data: { getReceipt: true, saleID: saleID },
        success: function(response) {
            var printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Receipt</title>');
            printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">');
            printWindow.document.write('<style>body{padding:20px; font-family: monospace;}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(response);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        },
        error: function() {
            Swal.fire('Error', 'Could not print receipt', 'error');
        }
    });
}

// Preserve filter values when clicking pagination
$(document).ready(function() {
    // Add loading effect on filter submit
    $('form').on('submit', function() {
        $('button[type="submit"]').html('<i class="bi bi-hourglass-split"></i> Loading...');
    });
});
</script>
</body>
</html>