<div class="modal fade modal-custom" id="editProductModal<?php echo $row['productID']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="../backend/routes.php?route=products">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Product: <?php echo htmlspecialchars($row['productName']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="productID" value="<?php echo $row['productID']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Code *</label>
                            <input type="text" name="productCode" class="form-control" value="<?php echo htmlspecialchars($row['productCode']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="productName" class="form-control" value="<?php echo htmlspecialchars($row['productName']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="productDesc" class="form-control" rows="2"><?php echo htmlspecialchars($row['productDesc'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="categoryID" class="form-select">
                                <option value="">Select Category</option>
                                <?php
                                $categories = mysqli_query($conn, "SELECT * FROM categories WHERE dateDeleted IS NULL");
                                while($cat = mysqli_fetch_assoc($categories)){
                                    $selected = ($cat['categoryID'] == $row['categoryID']) ? 'selected' : '';
                                    echo '<option value="' . $cat['categoryID'] . '" ' . $selected . '>' . htmlspecialchars($cat['categoryName']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control" value="<?php echo htmlspecialchars($row['barcode'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Price (₱) *</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $row['price']; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cost (₱) *</label>
                            <input type="number" step="0.01" name="cost" class="form-control" value="<?php echo $row['cost']; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $row['stock']; ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorderLevel" class="form-control" value="<?php echo $row['reorderLevel']; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="updateProduct" class="btn btn-warning">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>