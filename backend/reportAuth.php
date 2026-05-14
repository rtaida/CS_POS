<?php
require_once 'database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

// Handle delete all sales
if(isset($_GET['delete_all'])) {
    if(!isset($_SESSION['csrf_token'])){
        header("Location: ../frontend/sales.php?error=invalid_token");
        exit();
    }
    
    $dateFilter = $_GET['date_filter'] ?? 'today';
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $dateDeleted = date('Y-m-d H:i:s');
    
    $query = "UPDATE sales SET dateDeleted = '$dateDeleted' 
              WHERE DATE(saleDate) BETWEEN '$startDate' AND '$endDate' 
              AND dateDeleted IS NULL";
    
    if(mysqli_query($conn, $query)){
        header("Location: ../frontend/sales.php?deleted=true");
        exit();
    } else {
        header("Location: ../frontend/sales.php?error=delete_failed");
        exit();
    }
}

// Get Receipt for a specific sale
if(isset($_POST['getReceipt'])) {
    $saleID = $_POST['saleID'];
    
    // Get sale details
    $saleQuery = "SELECT * FROM sales WHERE saleID = '$saleID'";
    $saleResult = mysqli_query($conn, $saleQuery);
    $sale = mysqli_fetch_assoc($saleResult);
    
    // Get sale items
    $itemsQuery = "SELECT si.*, p.productName, p.productCode 
                   FROM sale_items si 
                   JOIN products p ON si.productID = p.productID 
                   WHERE si.saleID = '$saleID'";
    $itemsResult = mysqli_query($conn, $itemsQuery);
    
    // Generate receipt HTML
    ?>
    <div class="receipt-container" style="font-family: monospace; max-width: 300px; margin: 0 auto;">
        <div class="receipt-header text-center">
            <h4>CS POS</h4>
            <small>Sari-sari Store</small>
            <hr>
            <p><strong>Invoice:</strong> <?php echo $sale['invoiceNo']; ?><br>
            <strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($sale['saleDate'])); ?><br>
            <?php if($sale['customerName']): ?>
            <strong>Customer:</strong> <?php echo htmlspecialchars($sale['customerName']); ?><br>
            <?php endif; ?>
            </p>
            <hr>
        </div>
        
        <div class="receipt-items">
            <table style="width: 100%; font-size: 12px;">
                <thead>
                    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th</tr>
                </thead>
                <tbody>
                    <?php while($item = mysqli_fetch_assoc($itemsResult)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['productName']); ?></td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">₱<?php echo number_format($item['unitPrice'], 2); ?></td>
                        <td style="text-align: right;">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="receipt-total">
            <hr>
            <table style="width: 100%; font-size: 12px;">
                <tr><td>Subtotal:</td><td style="text-align: right;">₱<?php echo number_format($sale['totalAmount'], 2); ?></td></tr>
                <?php if($sale['discount'] > 0): ?>
                <tr><td>Discount:</td><td style="text-align: right;">-₱<?php echo number_format($sale['discount'], 2); ?></td></tr>
                <?php endif; ?>
                <tr><td>Tax (12%):</td><td style="text-align: right;">₱<?php echo number_format($sale['tax'], 2); ?></td></tr>
                <tr><td><strong>TOTAL:</strong></td><td style="text-align: right;"><strong>₱<?php echo number_format($sale['grandTotal'], 2); ?></strong></td></tr>
                <tr><td>Amount Paid:</td><td style="text-align: right;">₱<?php echo number_format($sale['amountPaid'], 2); ?></td></tr>
                <tr><td>Change:</td><td style="text-align: right;">₱<?php echo number_format($sale['changeDue'], 2); ?></td></tr>
            </table>
            <hr>
            <div class="text-center">
                <small>Payment Method: <?php echo ucfirst($sale['paymentMethod']); ?></small><br>
                <small>Thank you for your purchase!</small>
            </div>
        </div>
    </div>
    <?php
    exit();
}

// Get Last 7 Days Sales for Dashboard Chart
if(isset($_POST['getLast7DaysSales'])) {
    $labels = [];
    $values = [];
    
    for($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M d', strtotime($date));
        
        $query = "SELECT COALESCE(SUM(grandTotal), 0) as total FROM sales 
                  WHERE DATE(saleDate) = '$date' AND dateDeleted IS NULL";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        $values[] = (float)$row['total'];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['labels' => $labels, 'values' => $values]);
    exit();
}

// Get Payment Method Breakdown for Dashboard Chart
if(isset($_POST['getPaymentBreakdown'])) {
    $query = "SELECT paymentMethod, COALESCE(SUM(grandTotal), 0) as total 
              FROM sales 
              WHERE dateDeleted IS NULL 
              GROUP BY paymentMethod";
    $result = mysqli_query($conn, $query);
    
    $labels = [];
    $values = [];
    while($row = mysqli_fetch_assoc($result)) {
        $labels[] = ucfirst($row['paymentMethod']);
        $values[] = (float)$row['total'];
    }
    
    // If no data, provide default
    if(empty($labels)) {
        $labels = ['Cash', 'GCash', 'Bank Transfer'];
        $values = [0, 0, 0];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['labels' => $labels, 'values' => $values]);
    exit();
}

// Get Sales Summary
if(isset($_POST['getSalesSummary'])){
    $startDate = mysqli_real_escape_string($conn, $_POST['startDate']);
    $endDate = mysqli_real_escape_string($conn, $_POST['endDate']);
    
    $query = mysqli_query($conn, "CALL GetSalesSummary('$startDate', '$endDate')");
    $results = [];
    while($row = mysqli_fetch_assoc($query)){
        $results[] = $row;
    }
    mysqli_free_result($query);
    
    // Clear stored procedure results
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}

// Get Top Products
if(isset($_POST['getTopProducts'])){
    $limit = (int)($_POST['limit'] ?? 10);
    $startDate = mysqli_real_escape_string($conn, $_POST['startDate']);
    $endDate = mysqli_real_escape_string($conn, $_POST['endDate']);
    
    $query = mysqli_query($conn, "CALL GetTopProducts($limit, '$startDate', '$endDate')");
    $results = [];
    while($row = mysqli_fetch_assoc($query)){
        $results[] = $row;
    }
    mysqli_free_result($query);
    
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}

// Get Inventory Status
if(isset($_POST['getInventoryStatus'])){
    $query = mysqli_query($conn, "CALL GetInventoryStatus()");
    $results = [];
    while($row = mysqli_fetch_assoc($query)){
        $results[] = $row;
    }
    mysqli_free_result($query);
    
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}

// Get Profit & Loss
if(isset($_POST['getProfitLoss'])){
    $startDate = mysqli_real_escape_string($conn, $_POST['startDate']);
    $endDate = mysqli_real_escape_string($conn, $_POST['endDate']);
    
    $query = mysqli_query($conn, "CALL GetProfitLoss('$startDate', '$endDate')");
    $result = mysqli_fetch_assoc($query);
    mysqli_free_result($query);
    
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    // Ensure all fields exist
    $defaultResult = [
        'gross_revenue' => 0,
        'total_cost' => 0,
        'gross_profit' => 0,
        'total_discounts' => 0,
        'net_profit' => 0
    ];
    
    $result = array_merge($defaultResult, $result ?: []);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// Get Sales by Category
if(isset($_POST['getSalesByCategory'])){
    $startDate = mysqli_real_escape_string($conn, $_POST['startDate']);
    $endDate = mysqli_real_escape_string($conn, $_POST['endDate']);
    
    $query = mysqli_query($conn, "CALL GetSalesByCategory('$startDate', '$endDate')");
    $results = [];
    while($row = mysqli_fetch_assoc($query)){
        $results[] = $row;
    }
    mysqli_free_result($query);
    
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}

// Get Low Stock Products
if(isset($_POST['getLowStock'])){
    $query = mysqli_query($conn, "CALL GetLowStockProducts()");
    $results = [];
    while($row = mysqli_fetch_assoc($query)){
        $results[] = $row;
    }
    mysqli_free_result($query);
    
    while (mysqli_next_result($conn)) {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}

// Export Report to CSV
if(isset($_POST['exportReport'])){
    $reportType = $_POST['reportType'];
    $startDate = mysqli_real_escape_string($conn, $_POST['startDate']);
    $endDate = mysqli_real_escape_string($conn, $_POST['endDate']);
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_' . date('Ymd') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if($reportType == 'sales_summary'){
        fputcsv($output, ['Date', 'Transactions', 'Total Sales', 'Average Transaction', 'Total Discount', 'Total Tax']);
        $query = mysqli_query($conn, "CALL GetSalesSummary('$startDate', '$endDate')");
        while($row = mysqli_fetch_assoc($query)){
            fputcsv($output, $row);
        }
        mysqli_free_result($query);
        while (mysqli_next_result($conn)) {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
    } 
    elseif($reportType == 'top_products'){
        fputcsv($output, ['Product Code', 'Product Name', 'Category', 'Quantity Sold', 'Total Revenue']);
        $query = mysqli_query($conn, "CALL GetTopProducts($limit, '$startDate', '$endDate')");
        while($row = mysqli_fetch_assoc($query)){
            fputcsv($output, $row);
        }
        mysqli_free_result($query);
        while (mysqli_next_result($conn)) {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
    }
    elseif($reportType == 'inventory'){
        fputcsv($output, ['Product Code', 'Product Name', 'Category', 'Stock', 'Reorder Level', 'Status', 'Price']);
        $query = mysqli_query($conn, "CALL GetInventoryStatus()");
        while($row = mysqli_fetch_assoc($query)){
            fputcsv($output, $row);
        }
        mysqli_free_result($query);
        while (mysqli_next_result($conn)) {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
    }
    elseif($reportType == 'profit_loss'){
        fputcsv($output, ['Gross Revenue', 'Total Cost', 'Gross Profit', 'Total Discounts', 'Net Profit']);
        $query = mysqli_query($conn, "CALL GetProfitLoss('$startDate', '$endDate')");
        $row = mysqli_fetch_assoc($query);
        if($row){
            fputcsv($output, $row);
        }
        mysqli_free_result($query);
        while (mysqli_next_result($conn)) {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
    }
    elseif($reportType == 'category_sales'){
        fputcsv($output, ['Category', 'Transactions', 'Items Sold', 'Total Sales']);
        $query = mysqli_query($conn, "CALL GetSalesByCategory('$startDate', '$endDate')");
        while($row = mysqli_fetch_assoc($query)){
            fputcsv($output, $row);
        }
        mysqli_free_result($query);
        while (mysqli_next_result($conn)) {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        }
    }
    
    fclose($output);
    exit();
}

// If no valid request
header("Location: ../frontend/dashboard.php");
exit();
?>