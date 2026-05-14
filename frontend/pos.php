<?php
require_once '../backend/database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

$csrf_token = $_SESSION['csrf_token'];

// Handle AJAX requests for products
if(isset($_GET['getProducts']) && $_GET['getProducts'] == 'true') {
    header('Content-Type: application/json');
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    
    $query = "SELECT p.*, c.categoryName 
              FROM products p 
              LEFT JOIN categories c ON p.categoryID = c.categoryID 
              WHERE p.dateDeleted IS NULL";
    
    if($search != '') {
        $search = mysqli_real_escape_string($conn, $search);
        $query .= " AND (p.productName LIKE '%$search%' OR p.productCode LIKE '%$search%')";
    }
    
    if($category != 'all') {
        $category = mysqli_real_escape_string($conn, $category);
        $query .= " AND p.categoryID = '$category'";
    }
    
    $query .= " ORDER BY p.productName LIMIT 50";
    $result = mysqli_query($conn, $query);
    
    $products = [];
    while($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    echo json_encode($products);
    exit();
}

// Handle AJAX for getting current cart
if(isset($_POST['getCart']) && $_POST['getCart'] == 'true') {
    header('Content-Type: application/json');
    if(!isset($_SESSION['current_sale_id'])) {
        echo json_encode(['items' => []]);
        exit();
    }
    
    $saleID = $_SESSION['current_sale_id'];
    $query = "SELECT si.*, p.productName, p.productCode, p.price 
              FROM sale_items si 
              JOIN products p ON si.productID = p.productID 
              WHERE si.saleID = '$saleID'";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    while($row = mysqli_fetch_assoc($result)){
        $items[] = $row;
    }
    
    echo json_encode(['items' => $items]);
    exit();
}

// Handle AJAX for checking current sale
if(isset($_POST['checkCurrentSale']) && $_POST['checkCurrentSale'] == 'true') {
    header('Content-Type: application/json');
    if(isset($_SESSION['current_sale_id']) && isset($_SESSION['current_invoice'])) {
        echo json_encode([
            'saleID' => $_SESSION['current_sale_id'],
            'invoiceNo' => $_SESSION['current_invoice']
        ]);
    } else {
        echo json_encode(['saleID' => null]);
    }
    exit();
}

// Handle AJAX for starting new sale
if(isset($_POST['startSale']) && $_POST['startSale'] == 'true') {
    header('Content-Type: application/json');
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        exit();
    }
    
    $invoiceNo = 'INV-' . date('YmdHis') . '-' . rand(100, 999);
    
    $query = "INSERT INTO sales (invoiceNo, paymentMethod, amountPaid, saleDate) 
              VALUES ('$invoiceNo', 'cash', 0, NOW())";
    
    if(mysqli_query($conn, $query)) {
        $saleID = mysqli_insert_id($conn);
        $_SESSION['current_sale_id'] = $saleID;
        $_SESSION['current_invoice'] = $invoiceNo;
        echo json_encode(['status' => 'success', 'saleID' => $saleID, 'invoiceNo' => $invoiceNo]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}

// Handle AJAX for adding to cart
if(isset($_POST['addToCart']) && $_POST['addToCart'] == 'true') {
    header('Content-Type: application/json');
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        exit();
    }
    
    if(!isset($_SESSION['current_sale_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No active sale. Please start a new sale first.']);
        exit();
    }
    
    $saleID = $_SESSION['current_sale_id'];
    $productID = mysqli_real_escape_string($conn, $_POST['productID']);
    $quantity = (int)$_POST['quantity'];
    
    // Get product price and stock
    $productQuery = "SELECT price, stock FROM products WHERE productID = '$productID'";
    $productResult = mysqli_query($conn, $productQuery);
    $product = mysqli_fetch_assoc($productResult);
    
    if($product['stock'] < $quantity) {
        echo json_encode(['status' => 'error', 'message' => 'Insufficient stock. Available: ' . $product['stock']]);
        exit();
    }
    
    $unitPrice = $product['price'];
    $subtotal = $quantity * $unitPrice;
    
    // Check if product already in cart
    $checkQuery = "SELECT saleItemID, quantity FROM sale_items WHERE saleID = '$saleID' AND productID = '$productID'";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if(mysqli_num_rows($checkResult) > 0) {
        $existing = mysqli_fetch_assoc($checkResult);
        $newQuantity = $existing['quantity'] + $quantity;
        $newSubtotal = $newQuantity * $unitPrice;
        $updateQuery = "UPDATE sale_items SET quantity = '$newQuantity', subtotal = '$newSubtotal' WHERE saleItemID = '{$existing['saleItemID']}'";
        mysqli_query($conn, $updateQuery);
    } else {
        $insertQuery = "INSERT INTO sale_items (saleID, productID, quantity, unitPrice, subtotal) 
                        VALUES ('$saleID', '$productID', '$quantity', '$unitPrice', '$subtotal')";
        mysqli_query($conn, $insertQuery);
    }
    
    echo json_encode(['status' => 'success']);
    exit();
}

// Handle AJAX for removing cart item
if(isset($_POST['removeCartItem']) && $_POST['removeCartItem'] == 'true') {
    header('Content-Type: application/json');
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error']);
        exit();
    }
    
    $saleItemID = mysqli_real_escape_string($conn, $_POST['saleItemID']);
    $query = "DELETE FROM sale_items WHERE saleItemID = '$saleItemID'";
    
    if(mysqli_query($conn, $query)){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Handle AJAX for updating quantity
if(isset($_POST['updateQuantity']) && $_POST['updateQuantity'] == 'true') {
    header('Content-Type: application/json');
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error']);
        exit();
    }
    
    $saleItemID = mysqli_real_escape_string($conn, $_POST['saleItemID']);
    $quantity = (int)$_POST['quantity'];
    
    // Get unit price
    $getPriceQuery = "SELECT unitPrice FROM sale_items WHERE saleItemID = '$saleItemID'";
    $priceResult = mysqli_query($conn, $getPriceQuery);
    $priceRow = mysqli_fetch_assoc($priceResult);
    $subtotal = $quantity * $priceRow['unitPrice'];
    
    $query = "UPDATE sale_items SET quantity = '$quantity', subtotal = '$subtotal' WHERE saleItemID = '$saleItemID'";
    
    if(mysqli_query($conn, $query)){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Handle AJAX for finalizing sale
if(isset($_POST['finalizeSale']) && $_POST['finalizeSale'] == 'true') {
    header('Content-Type: application/json');
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
        exit();
    }
    
    if(!isset($_SESSION['current_sale_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No active sale']);
        exit();
    }
    
    $saleID = $_SESSION['current_sale_id'];
    $discount = (float)$_POST['discount'];
    $amountPaid = (float)$_POST['amountPaid'];
    $paymentMethod = mysqli_real_escape_string($conn, $_POST['paymentMethod']);
    $customerName = isset($_POST['customerName']) ? mysqli_real_escape_string($conn, $_POST['customerName']) : '';
    
    // Calculate totals
    $totalsQuery = "SELECT SUM(subtotal) as total FROM sale_items WHERE saleID = '$saleID'";
    $totalsResult = mysqli_query($conn, $totalsQuery);
    $totals = mysqli_fetch_assoc($totalsResult);
    $subtotal = $totals['total'] ?? 0;
    $tax = $subtotal * 0.12;
    $grandTotal = $subtotal - $discount + $tax;
    $changeDue = $amountPaid - $grandTotal;
    
    // Update stock
    $itemsQuery = "SELECT productID, quantity FROM sale_items WHERE saleID = '$saleID'";
    $itemsResult = mysqli_query($conn, $itemsQuery);
    while($item = mysqli_fetch_assoc($itemsResult)) {
        $updateStock = "UPDATE products SET stock = stock - {$item['quantity']} WHERE productID = {$item['productID']}";
        mysqli_query($conn, $updateStock);
    }
    
    // Update sale record
    $updateQuery = "UPDATE sales SET 
                    totalAmount = '$subtotal',
                    discount = '$discount',
                    tax = '$tax',
                    grandTotal = '$grandTotal',
                    amountPaid = '$amountPaid',
                    changeDue = '$changeDue',
                    paymentMethod = '$paymentMethod',
                    customerName = '$customerName'
                    WHERE saleID = '$saleID'";
    
    if(mysqli_query($conn, $updateQuery)){
        // Clear session
        unset($_SESSION['current_sale_id']);
        unset($_SESSION['current_invoice']);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CS POS - Point of Sale</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

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
    padding: 20px;
    min-height: 100vh;
}

.pos-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
}

.products-grid {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.search-bar {
    margin-bottom: 20px;
}

.search-bar input {
    width: 100%;
    padding: 12px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 15px;
    font-size: 16px;
}

.product-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    padding: 5px;
}

.product-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 15px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.product-card .product-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}

.product-card .product-icon i {
    font-size: 30px;
    color: #667eea;
}

.product-card .product-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 5px;
}

.product-card .product-price {
    color: #667eea;
    font-weight: 700;
    font-size: 16px;
}

.product-card .product-stock {
    font-size: 11px;
    color: #999;
    margin-top: 5px;
}

.cart-section {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 40px);
    position: sticky;
    top: 20px;
}

.cart-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.cart-header h4 {
    font-weight: 600;
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 15px;
    max-height: 400px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item-info {
    flex: 1;
}

.cart-item-name {
    font-weight: 500;
    font-size: 14px;
}

.cart-item-price {
    font-size: 12px;
    color: #666;
}

.cart-item-qty {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-btn {
    width: 25px;
    height: 25px;
    border-radius: 8px;
    border: none;
    background: #f0f2f5;
    font-weight: bold;
    cursor: pointer;
}

.cart-item-total {
    font-weight: 700;
    color: #667eea;
    min-width: 70px;
    text-align: right;
}

.remove-item {
    color: #ff6b6b;
    cursor: pointer;
    margin-left: 10px;
    font-size: 18px;
}

.cart-summary {
    border-top: 2px solid #f0f0f0;
    padding-top: 15px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.total-row {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
    border-top: 1px dashed #e0e0e0;
    padding-top: 10px;
    margin-top: 10px;
}

.payment-section {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #f0f0f0;
}

.payment-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    margin-bottom: 10px;
}

.checkout-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40,167,69,0.4);
}

.new-sale-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    width: 100%;
    font-weight: 600;
}

.new-sale-btn:hover {
    background: #0056b3;
}

.category-filter {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.category-btn {
    padding: 8px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    background: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

.category-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
    .pos-container {
        grid-template-columns: 1fr;
    }
    .cart-section {
        position: static;
        height: auto;
    }
}

.empty-cart {
    text-align: center;
    color: #999;
    padding: 40px 20px;
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    <div class="pos-container">
        <!-- Products Section -->
        <div class="products-grid">
            <div class="search-bar">
                <input type="text" id="searchProduct" placeholder="🔍 Search products..." class="form-control">
            </div>
            
            <div class="category-filter" id="categoryFilter">
                <button class="category-btn active" data-category="all">All Products</button>
                <?php
                $categories = mysqli_query($conn, "SELECT * FROM categories WHERE dateDeleted IS NULL");
                while($cat = mysqli_fetch_assoc($categories)){
                    echo '<button class="category-btn" data-category="' . $cat['categoryID'] . '">' . htmlspecialchars($cat['categoryName']) . '</button>';
                }
                ?>
            </div>
            
            <div class="product-cards" id="productGrid">
                <div class="text-center text-muted py-5">Loading products...</div>
            </div>
        </div>
        
        <!-- Cart Section -->
        <div class="cart-section">
            <button class="new-sale-btn" onclick="startNewSale()">
                <i class="bi bi-plus-circle"></i> New Sale
            </button>
            
            <div class="cart-header">
                <h4><i class="bi bi-cart"></i> Current Sale</h4>
                <small class="text-muted" id="invoiceDisplay"></small>
            </div>
            
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">No items in cart</div>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Discount:</span>
                    <input type="number" id="discount" value="0" step="1" min="0" style="width: 100px; text-align: right;" class="form-control form-control-sm">
                </div>
                <div class="summary-row">
                    <span>Tax (12%):</span>
                    <span id="tax">₱0.00</span>
                </div>
                <div class="summary-row total-row">
                    <span>Total:</span>
                    <span id="grandTotal">₱0.00</span>
                </div>
            </div>
            
            <div class="payment-section">
                <input type="number" id="amountPaid" placeholder="Amount Received" class="payment-input" step="0.01">
                <div class="summary-row">
                    <span>Change:</span>
                    <span id="changeDue" class="text-success fw-bold">₱0.00</span>
                </div>
                <select id="paymentMethod" class="form-control mb-2">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
                <input type="text" id="customerName" placeholder="Customer Name (Optional)" class="payment-input mb-2">
                <button class="checkout-btn" onclick="finalizeSale()">
                    <i class="bi bi-check-circle"></i> Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCart = [];

$(document).ready(function() {
    loadProducts();
    checkCurrentSale();
    
    $('#searchProduct').on('keyup', function() {
        loadProducts($(this).val());
    });
    
    $('.category-btn').on('click', function() {
        $('.category-btn').removeClass('active');
        $(this).addClass('active');
        loadProducts($('#searchProduct').val(), $(this).data('category'));
    });
    
    $('#discount').on('input', function() {
        updateTotals();
    });
    
    $('#amountPaid').on('input', function() {
        calculateChange();
    });
});

function loadProducts(search = '', category = 'all') {
    $.ajax({
        url: window.location.href,
        method: 'GET',
        data: { getProducts: true, search: search, category: category },
        dataType: 'json',
        success: function(products) {
            let html = '';
            if(products.length === 0) {
                html = '<div class="text-center text-muted py-5">No products found</div>';
            } else {
                products.forEach(product => {
                    let stockClass = product.stock <= product.reorderLevel ? 'text-danger' : 'text-muted';
                    let disabled = product.stock <= 0 ? 'style="opacity:0.5; cursor:not-allowed;"' : '';
                    let onclickAttr = product.stock > 0 ? `onclick="addToCart(${product.productID}, '${product.productName.replace(/'/g, "\\'")}', ${product.price})"` : '';
                    
                    html += `
                        <div class="product-card" ${onclickAttr} ${disabled}>
                            <div class="product-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="product-name">${product.productName}</div>
                            <div class="product-price">₱${parseFloat(product.price).toFixed(2)}</div>
                            <div class="product-stock ${stockClass}">Stock: ${product.stock}</div>
                        </div>
                    `;
                });
            }
            $('#productGrid').html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading products:', error);
            $('#productGrid').html('<div class="text-center text-danger py-5">Error loading products. Please refresh.</div>');
        }
    });
}

function checkCurrentSale() {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { checkCurrentSale: true },
        dataType: 'json',
        success: function(data) {
            if(data.saleID) {
                $('#invoiceDisplay').text('Invoice: ' + data.invoiceNo);
                loadCart();
            } else {
                // Auto-start a new sale if none exists
                startNewSaleSilent();
            }
        }
    });
}

function startNewSaleSilent() {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { 
            startSale: true, 
            csrf_token: '<?php echo $csrf_token; ?>' 
        },
        dataType: 'json',
        success: function(data) {
            if(data.status === 'success') {
                $('#invoiceDisplay').text('Invoice: ' + data.invoiceNo);
                loadCart();
            }
        }
    });
}

function startNewSale() {
    Swal.fire({
        title: 'Start New Sale',
        text: 'Create a new transaction? Current cart will be cleared.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Start'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: { 
                    startSale: true, 
                    csrf_token: '<?php echo $csrf_token; ?>' 
                },
                dataType: 'json',
                success: function(data) {
                    if(data.status === 'success') {
                        $('#invoiceDisplay').text('Invoice: ' + data.invoiceNo);
                        loadCart();
                        Swal.fire('Success', 'New sale started!', 'success');
                    } else {
                        Swal.fire('Error', data.message || 'Could not start new sale', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Could not start new sale', 'error');
                }
            });
        }
    });
}

function addToCart(productID, productName, price) {
    Swal.fire({
        title: 'Enter Quantity',
        text: productName,
        input: 'number',
        inputValue: 1,
        inputAttributes: { min: 1, step: 1 },
        showCancelButton: true,
        confirmButtonText: 'Add to Cart'
    }).then((result) => {
        if(result.isConfirmed && result.value > 0) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    addToCart: true,
                    productID: productID,
                    quantity: result.value,
                    csrf_token: '<?php echo $csrf_token; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        loadCart();
                        Swal.fire('Added!', 'Product added to cart', 'success');
                    } else {
                        Swal.fire('Error', response.message || 'Could not add product', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Could not add product to cart', 'error');
                }
            });
        }
    });
}

function loadCart() {
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { getCart: true },
        dataType: 'json',
        success: function(data) {
            currentCart = data.items || [];
            renderCart();
            updateTotals();
        }
    });
}

function renderCart() {
    let html = '';
    if(currentCart.length === 0) {
        html = '<div class="empty-cart">No items in cart</div>';
    } else {
        currentCart.forEach(item => {
            html += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.productName}</div>
                        <div class="cart-item-price">₱${parseFloat(item.unitPrice).toFixed(2)}</div>
                    </div>
                    <div class="cart-item-qty">
                        <button class="qty-btn" onclick="updateQuantity(${item.saleItemID}, ${item.quantity - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button class="qty-btn" onclick="updateQuantity(${item.saleItemID}, ${item.quantity + 1})">+</button>
                    </div>
                    <div class="cart-item-total">₱${parseFloat(item.subtotal).toFixed(2)}</div>
                    <i class="bi bi-trash remove-item" onclick="removeItem(${item.saleItemID})"></i>
                </div>
            `;
        });
    }
    $('#cartItems').html(html);
}

function updateQuantity(saleItemID, newQty) {
    if(newQty <= 0) {
        removeItem(saleItemID);
        return;
    }
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: {
            updateQuantity: true,
            saleItemID: saleItemID,
            quantity: newQty,
            csrf_token: '<?php echo $csrf_token; ?>'
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                loadCart();
            }
        }
    });
}

function removeItem(saleItemID) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'This item will be removed from cart.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    removeCartItem: true,
                    saleItemID: saleItemID,
                    csrf_token: '<?php echo $csrf_token; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        loadCart();
                    }
                }
            });
        }
    });
}

function updateTotals() {
    let subtotal = 0;
    currentCart.forEach(item => {
        subtotal += parseFloat(item.subtotal);
    });
    
    let discount = parseFloat($('#discount').val()) || 0;
    let tax = subtotal * 0.12;
    let grandTotal = subtotal - discount + tax;
    
    $('#subtotal').text('₱' + subtotal.toFixed(2));
    $('#tax').text('₱' + tax.toFixed(2));
    $('#grandTotal').text('₱' + grandTotal.toFixed(2));
    
    calculateChange();
}

function calculateChange() {
    let grandTotalText = $('#grandTotal').text();
    let grandTotal = parseFloat(grandTotalText.replace('₱', ''));
    let amountPaid = parseFloat($('#amountPaid').val()) || 0;
    let change = amountPaid - grandTotal;
    $('#changeDue').text('₱' + (change > 0 ? change.toFixed(2) : '0.00'));
}

function finalizeSale() {
    let discount = $('#discount').val();
    let amountPaid = $('#amountPaid').val();
    let paymentMethod = $('#paymentMethod').val();
    let customerName = $('#customerName').val();
    let grandTotalText = $('#grandTotal').text();
    let grandTotal = parseFloat(grandTotalText.replace('₱', ''));
    
    if(currentCart.length === 0) {
        Swal.fire('Error', 'Cart is empty. Add items first.', 'error');
        return;
    }
    
    if(!amountPaid || parseFloat(amountPaid) < grandTotal) {
        Swal.fire('Error', 'Insufficient payment amount', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Complete Sale?',
        text: 'Total: ₱' + grandTotal.toFixed(2),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Complete'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: {
                    finalizeSale: true,
                    discount: discount,
                    amountPaid: amountPaid,
                    paymentMethod: paymentMethod,
                    customerName: customerName,
                    csrf_token: '<?php echo $csrf_token; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        Swal.fire('Success!', 'Sale completed successfully!', 'success').then(() => {
                            // Reset form
                            $('#discount').val(0);
                            $('#amountPaid').val('');
                            $('#customerName').val('');
                            $('#cartItems').html('<div class="empty-cart">No items in cart</div>');
                            currentCart = [];
                            updateTotals();
                            // Start new sale automatically
                            startNewSaleSilent();
                            // Refresh products to update stock
                            loadProducts();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Could not complete sale', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Could not complete sale', 'error');
                }
            });
        }
    });
}

// Auto-refresh products every 30 seconds
setInterval(function() {
    loadProducts($('#searchProduct').val(), $('.category-btn.active').data('category'));
}, 30000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>