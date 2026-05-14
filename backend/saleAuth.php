<?php
require_once 'database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['startSale'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    $invoiceNo = 'INV-' . date('YmdHis') . '-' . rand(100, 999);
    $paymentMethod = $_POST['paymentMethod'];
    $amountPaid = $_POST['amountPaid'];
    $customerName = $_POST['customerName'] ?: NULL;
    $customerPhone = $_POST['customerPhone'] ?: NULL;
    
    $query = mysqli_query($conn, "CALL CreateSale('$invoiceNo', '$paymentMethod', '$amountPaid', " . ($customerName ? "'$customerName'" : "NULL") . ", " . ($customerPhone ? "'$customerPhone'" : "NULL") . ")");
    
    $result = mysqli_fetch_assoc($query);
    $saleID = $result['saleID'];
    
    $_SESSION['current_sale_id'] = $saleID;
    $_SESSION['current_invoice'] = $invoiceNo;
    
    header("Location: ../frontend/pos.php?sale_started");
    exit();
}

if(isset($_POST['addToCart'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        exit();
    }
    
    $saleID = $_SESSION['current_sale_id'];
    $productID = $_POST['productID'];
    $quantity = $_POST['quantity'];
    
    $getPrice = "SELECT price FROM products WHERE productID = $productID";
    $priceResult = mysqli_query($conn, $getPrice);
    $priceRow = mysqli_fetch_assoc($priceResult);
    $unitPrice = $priceRow['price'];
    
    $query = mysqli_query($conn, "CALL AddSaleItem('$saleID', '$productID', '$quantity', '$unitPrice')");
    $result = mysqli_fetch_assoc($query);
    
    if($result['status'] == 'SUCCESS'){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient stock. Available: ' . $result['available_stock']]);
    }
    exit();
}

if(isset($_POST['finalizeSale'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        header("Location: ../index.php");
        exit();
    }
    
    $saleID = $_SESSION['current_sale_id'];
    $discount = $_POST['discount'] ?? 0;
    $tax = $_POST['tax'] ?? 0;
    
    $query = mysqli_query($conn, "CALL FinalizeSale('$saleID', '$discount', '$tax')");
    $result = mysqli_fetch_assoc($query);
    
    if($result['status'] == 'SUCCESS'){
        unset($_SESSION['current_sale_id']);
        unset($_SESSION['current_invoice']);
        header("Location: ../frontend/sales.php?receipt_saved");
        exit();
    }
}

if(isset($_POST['getCart'])){
    if(!isset($_SESSION['current_sale_id'])){
        echo json_encode(['items' => []]);
        exit();
    }
    
    $saleID = $_SESSION['current_sale_id'];
    
    $query = "SELECT si.*, p.productName, p.productCode 
              FROM sale_items si 
              JOIN products p ON si.productID = p.productID 
              WHERE si.saleID = $saleID";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    while($row = mysqli_fetch_assoc($result)){
        $items[] = $row;
    }
    
    echo json_encode(['items' => $items]);
    exit();
}

if(isset($_POST['removeCartItem'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error']);
        exit();
    }
    
    $saleItemID = $_POST['saleItemID'];
    
    $query = "DELETE FROM sale_items WHERE saleItemID = $saleItemID";
    if(mysqli_query($conn, $query)){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}
?>