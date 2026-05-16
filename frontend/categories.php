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
<title>CS POS - Categories</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.category-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    height: 100%;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.category-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}

.category-icon i {
    font-size: 32px;
    color: #667eea;
}

.category-name {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
}

.category-desc {
    color: #666;
    font-size: 13px;
    margin-bottom: 15px;
}

.category-stats {
    font-size: 12px;
    color: #999;
    margin-bottom: 15px;
}

.btn-category-action {
    padding: 6px 15px;
    border-radius: 12px;
    font-size: 13px;
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="bi bi-tags"></i> Category Management</h3>
            <p class="text-muted mb-0">Organize your products by categories</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle"></i> Add Category
        </button>
    </div>
    
    <?php include "message/categoryMessageAuth.php"; ?>
    
    <div class="row g-4">
        <?php
        $query = "SELECT c.*, COUNT(p.productID) as product_count 
                  FROM categories c 
                  LEFT JOIN products p ON c.categoryID = p.categoryID AND p.dateDeleted IS NULL
                  WHERE c.dateDeleted IS NULL 
                  GROUP BY c.categoryID 
                  ORDER BY c.categoryName";
        $result = mysqli_query($conn, $query);
        while($row = mysqli_fetch_assoc($result)):
        ?>
        <div class="col-md-4 col-lg-3">
            <div class="category-card">
                <div class="category-icon">
                    <i class="bi bi-folder"></i>
                </div>
                <div class="category-name"><?php echo htmlspecialchars($row['categoryName']); ?></div>
                <div class="category-desc"><?php echo htmlspecialchars(substr($row['categoryDesc'] ?? '', 0, 60)); ?></div>
                <div class="category-stats">
                    <i class="bi bi-box"></i> <?php echo $row['product_count']; ?> products
                </div>
                <div>
                    <button class="btn btn-sm btn-warning btn-category-action" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $row['categoryID']; ?>">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger btn-category-action" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?php echo $row['categoryID']; ?>">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Edit Modal -->
        <div class="modal fade" id="editCategoryModal<?php echo $row['categoryID']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="../backend/routes.php?route=categories">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="categoryID" value="<?php echo $row['categoryID']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Category Name *</label>
                                <input type="text" name="categoryName" class="form-control" value="<?php echo htmlspecialchars($row['categoryName']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="categoryDesc" class="form-control" rows="3"><?php echo htmlspecialchars($row['categoryDesc']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="updateCategory" class="btn btn-warning">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Delete Modal -->
        <div class="modal fade" id="deleteCategoryModal<?php echo $row['categoryID']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="../backend/routes.php?route=categories">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Delete Category</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="categoryID" value="<?php echo $row['categoryID']; ?>">
                            <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($row['categoryName']); ?></strong>?</p>
                            <?php if($row['product_count'] > 0): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> This category has <?php echo $row['product_count']; ?> products. Deleting will remove the category association.
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="DeleteCategory" class="btn btn-danger">Yes, Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="../backend/routes.php?route=categories">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="categoryName" class="form-control" required placeholder="e.g., Beverages, Snacks, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="categoryDesc" class="form-control" rows="3" placeholder="Brief description of this category"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="categoryAuth" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>