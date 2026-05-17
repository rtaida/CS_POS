<?php
require_once '../backend/database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}

$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CS POS - Products</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.products-header {
    background: white;
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.product-table-container {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.table-custom {
    border-radius: 15px;
    overflow: hidden;
}

.table-custom thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stock-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.stock-high {
    background: #d4edda;
    color: #155724;
}

.stock-low {
    background: #fff3cd;
    color: #856404;
}

.stock-out {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-sm-custom {
    padding: 5px 12px;
    border-radius: 10px;
    font-size: 12px;
}

.modal-custom .modal-content {
    border-radius: 20px;
    border: none;
}

.modal-custom .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px 20px 0 0;
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    
    <div class="products-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="mb-1"><i class="bi bi-box-seam"></i> Product Management</h3>
                <p class="text-muted mb-0">Manage your store inventory and product details</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle"></i> Add New Product
            </button>
        </div>
    </div>
    
    
    <?php include "message/productMessageAuth.php"; ?>
    
    
    <div class="product-table-container">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="searchTable" class="form-control rounded-pill" placeholder="🔍 Search products...">
            </div>
            <div class="col-md-3">
                <select id="filterCategory" class="form-select rounded-pill">
                    <option value="">All Categories</option>
                    <?php
                    $categories = mysqli_query($conn, "SELECT * FROM categories WHERE dateDeleted IS NULL");
                    while($cat = mysqli_fetch_assoc($categories)){
                        echo '<option value="' . $cat['categoryID'] . '">' . htmlspecialchars($cat['categoryName']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterStock" class="form-select rounded-pill">
                    <option value="">All Stock Status</option>
                    <option value="low">Low Stock (≤ Reorder Level)</option>
                    <option value="out">Out of Stock (0)</option>
                    <option value="in">In Stock</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-custom" id="productsTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Cost</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.*, c.categoryName 
                             FROM products p 
                             LEFT JOIN categories c ON p.categoryID = c.categoryID 
                             WHERE p.dateDeleted IS NULL 
                             ORDER BY p.productName";
                    $result = mysqli_query($conn, $query);
                    while($row = mysqli_fetch_assoc($result)):
                        $stockStatus = '';
                        $stockClass = '';
                        if($row['stock'] <= 0){
                            $stockStatus = 'Out of Stock';
                            $stockClass = 'stock-out';
                        } elseif($row['stock'] <= $row['reorderLevel']){
                            $stockStatus = 'Low Stock';
                            $stockClass = 'stock-low';
                        } else {
                            $stockStatus = 'In Stock';
                            $stockClass = 'stock-high';
                        }
                    ?>
                    <tr data-category-id="<?php echo $row['categoryID']; ?>" data-reorder-level="<?php echo $row['reorderLevel']; ?>">
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['productCode']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($row['productName']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['categoryName'] ?? 'Uncategorized'); ?></td>
                        <td class="text-success fw-bold">₱<?php echo number_format($row['price'], 2); ?></td>
                        <td class="text-muted">₱<?php echo number_format($row['cost'], 2); ?></td>
                        <td><?php echo $row['stock']; ?></td>
                        <td><span class="stock-badge <?php echo $stockClass; ?>"><?php echo $stockStatus; ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-warning btn-sm-custom" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $row['productID']; ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger btn-sm-custom" data-bs-toggle="modal" data-bs-target="#deleteProductModal<?php echo $row['productID']; ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Edit Modal -->
                    <?php include "modal/productModalAuth.php"; ?>
                    
                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteProductModal<?php echo $row['productID']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST" action="../backend/productAuth.php">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title">Delete Product</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="productID" value="<?php echo $row['productID']; ?>">
                                        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($row['productName']); ?></strong>?</p>
                                        <p class="text-muted small">This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="DeleteProduct" class="btn btn-danger">Yes, Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade modal-custom" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="../backend/productAuth.php">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Code *</label>
                            <input type="text" name="productCode" class="form-control" required placeholder="e.g., BEV001">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="productName" class="form-control" required placeholder="e.g., Coca-Cola 1.5L">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="productDesc" class="form-control" rows="2" placeholder="Product description..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="categoryID" class="form-select">
                                <option value="">Select Category</option>
                                <?php
                                $categories = mysqli_query($conn, "SELECT * FROM categories WHERE dateDeleted IS NULL");
                                while($cat = mysqli_fetch_assoc($categories)){
                                    echo '<option value="' . $cat['categoryID'] . '">' . htmlspecialchars($cat['categoryName']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Barcode/SKU</label>
                            <input type="text" name="barcode" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cost (₱) *</label>
                            <input type="number" step="0.01" name="cost" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Initial Stock *</label>
                            <input type="number" name="stock" class="form-control" required placeholder="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reorder Level *</label>
                            <input type="number" name="reorderLevel" class="form-control" value="5" required>
                            <small class="text-muted">Alert when stock drops below this number</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image_url" class="form-control" placeholder="Optional product image URL">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="productAuth" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

function filterProducts() {
    let search = $('#searchTable').val().toLowerCase();
    let categoryID = $('#filterCategory').val();
    let stockFilter = $('#filterStock').val();
    
    $('#productsTable tbody tr').each(function() {
        let $row = $(this);
        let name = $row.find('td:eq(1)').text().toLowerCase();
        let code = $row.find('td:eq(0)').text().toLowerCase();
        
        
        let rowCategoryID = $row.data('category-id') || '';
        
        
        let stockText = $row.find('td:eq(5)').text().trim();
        let stock = parseInt(stockText) || 0;
        
        
        let reorderLevel = parseInt($row.data('reorder-level')) || 5;
        
        
        let matchSearch = name.includes(search) || code.includes(search);
        
        
        let matchCategory = categoryID === '' || rowCategoryID == categoryID;
        
        
        let matchStock = true;
        if(stockFilter === 'low') {
            matchStock = stock <= reorderLevel && stock > 0;
        } else if(stockFilter === 'out') {
            matchStock = stock === 0;
        } else if(stockFilter === 'in') {
            matchStock = stock > reorderLevel;
        }
        

        if(matchSearch && matchCategory && matchStock) {
            $row.show();
        } else {
            $row.hide();
        }
    });
}


$('#searchTable, #filterCategory, #filterStock').on('keyup change', function() {
    filterProducts();
});


filterProducts();


let searchTimeout;
$('#searchTable').on('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        filterProducts();
    }, 300);
});
</script>
</body>
</html>