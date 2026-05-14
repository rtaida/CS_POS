CREATE DATABASE cs_pos;
USE cs_pos;

-- Categories Table
CREATE TABLE categories (
    categoryID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoryName VARCHAR(100) NOT NULL UNIQUE,
    categoryDesc TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    dateDeleted TIMESTAMP NULL
) ENGINE=InnoDB;

-- Products Table
CREATE TABLE products (
    productID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    productCode VARCHAR(50) NOT NULL UNIQUE,
    productName VARCHAR(200) NOT NULL,
    productDesc TEXT,
    categoryID INT UNSIGNED,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    reorderLevel INT DEFAULT 5,
    barcode VARCHAR(100),
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    dateDeleted TIMESTAMP NULL,
    FOREIGN KEY (categoryID) REFERENCES categories(categoryID) ON DELETE SET NULL,
    INDEX idx_product_code (productCode),
    INDEX idx_product_name (productName)
) ENGINE=InnoDB;

-- Sales Table
CREATE TABLE sales (
    saleID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoiceNo VARCHAR(50) NOT NULL UNIQUE,
    totalAmount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    grandTotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amountPaid DECIMAL(12,2) DEFAULT 0.00,
    changeDue DECIMAL(12,2) DEFAULT 0.00,
    paymentMethod ENUM('cash', 'gcash', 'bank_transfer') DEFAULT 'cash',
    customerName VARCHAR(100),
    customerPhone VARCHAR(20),
    saleDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    dateDeleted TIMESTAMP NULL,
    INDEX idx_invoice (invoiceNo),
    INDEX idx_sale_date (saleDate)
) ENGINE=InnoDB;

-- Sale Items Table
CREATE TABLE sale_items (
    saleItemID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    saleID INT UNSIGNED NOT NULL,
    productID INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unitPrice DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (saleID) REFERENCES sales(saleID) ON DELETE CASCADE,
    FOREIGN KEY (productID) REFERENCES products(productID),
    INDEX idx_sale_product (saleID, productID)
) ENGINE=InnoDB;

-- Users Table
CREATE TABLE users (
    userID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier') DEFAULT 'cashier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    dateDeleted TIMESTAMP NULL
) ENGINE=InnoDB;

-- Stock Adjustments Table
CREATE TABLE stock_adjustments (
    adjID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    productID INT UNSIGNED NOT NULL,
    adjustmentType ENUM('add', 'remove', 'return') NOT NULL,
    quantity INT NOT NULL,
    reason TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (productID) REFERENCES products(productID),
    INDEX idx_product_adjustment (productID)
) ENGINE=InnoDB;

-- Insert Default Data
INSERT INTO users (fullName, username, email, password, role) VALUES
('Admin User', 'admin', 'admin@cspos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO categories (categoryName, categoryDesc) VALUES
('Beverages', 'Soft drinks, juices, water, energy drinks'),
('Snacks', 'Chips, biscuits, candies, chocolates'),
('Instant Food', 'Noodles, canned goods, instant meals'),
('Household', 'Detergent, soap, cleaning supplies'),
('Personal Care', 'Shampoo, toothpaste, skincare'),
('Rice & Groceries', 'Rice, sugar, cooking oil, basic groceries');

-- Insert Sample Products
INSERT INTO products (productCode, productName, productDesc, categoryID, price, cost, stock, reorderLevel) VALUES
('BEV001', 'Coca-Cola 1.5L', 'Carbonated soft drink', 1, 85.00, 70.00, 50, 10),
('BEV002', 'Mountain Dew 1L', 'Citrus flavored soda', 1, 75.00, 60.00, 40, 8),
('SNK001', 'Lays Classic 55g', 'Potato chips', 2, 65.00, 50.00, 100, 20),
('SNK002', 'Oreo Cookies 137g', 'Chocolate sandwich cookies', 2, 55.00, 42.00, 80, 15),
('INS001', 'Lucky Me Pancit Canton', 'Instant noodles', 3, 15.00, 12.00, 200, 50),
('INS002', 'Argentina Corned Beef', 'Canned corned beef', 3, 45.00, 38.00, 60, 12),
('HHD001', 'Surf Laundry Powder 150g', 'Laundry detergent', 4, 25.00, 20.00, 90, 20),
('PCR001', 'Colgate Toothpaste 50ml', 'Mint flavored toothpaste', 5, 35.00, 28.00, 70, 15);

-- Stored Procedures for Ad-hoc Reporting

DELIMITER //

-- Get Sales Summary by Date Range
CREATE PROCEDURE GetSalesSummary(IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT 
        DATE(saleDate) as sale_date,
        COUNT(*) as transaction_count,
        SUM(grandTotal) as total_sales,
        AVG(grandTotal) as average_transaction,
        SUM(discount) as total_discount,
        SUM(tax) as total_tax
    FROM sales
    WHERE DATE(saleDate) BETWEEN startDate AND endDate
        AND dateDeleted IS NULL
    GROUP BY DATE(saleDate)
    ORDER BY sale_date DESC;
END //

-- Get Top Selling Products
CREATE PROCEDURE GetTopProducts(IN limitCount INT, IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT 
        p.productID,
        p.productCode,
        p.productName,
        c.categoryName,
        SUM(si.quantity) as total_quantity_sold,
        SUM(si.subtotal) as total_revenue
    FROM sale_items si
    JOIN products p ON si.productID = p.productID
    JOIN sales s ON si.saleID = s.saleID
    LEFT JOIN categories c ON p.categoryID = c.categoryID
    WHERE DATE(s.saleDate) BETWEEN startDate AND endDate
        AND s.dateDeleted IS NULL
        AND p.dateDeleted IS NULL
    GROUP BY p.productID, p.productCode, p.productName, c.categoryName
    ORDER BY total_quantity_sold DESC
    LIMIT limitCount;
END //

-- Get Inventory Status
CREATE PROCEDURE GetInventoryStatus()
BEGIN
    SELECT 
        p.productID,
        p.productCode,
        p.productName,
        c.categoryName,
        p.stock,
        p.reorderLevel,
        CASE 
            WHEN p.stock <= 0 THEN 'Out of Stock'
            WHEN p.stock <= p.reorderLevel THEN 'Low Stock'
            ELSE 'In Stock'
        END as stock_status,
        p.price
    FROM products p
    LEFT JOIN categories c ON p.categoryID = c.categoryID
    WHERE p.dateDeleted IS NULL
    ORDER BY 
        CASE 
            WHEN p.stock <= 0 THEN 1
            WHEN p.stock <= p.reorderLevel THEN 2
            ELSE 3
        END,
        p.productName;
END //

-- Get Daily Sales Report
CREATE PROCEDURE GetDailySalesReport(IN reportDate DATE)
BEGIN
    SELECT 
        s.invoiceNo,
        s.saleDate,
        COUNT(si.saleItemID) as item_count,
        s.grandTotal,
        s.paymentMethod,
        s.customerName
    FROM sales s
    JOIN sale_items si ON s.saleID = si.saleID
    WHERE DATE(s.saleDate) = reportDate
        AND s.dateDeleted IS NULL
    GROUP BY s.saleID, s.invoiceNo, s.saleDate, s.grandTotal, s.paymentMethod, s.customerName
    ORDER BY s.saleDate DESC;
END //

-- Get Profit/Loss Report
CREATE PROCEDURE GetProfitLoss(IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT 
        SUM(si.subtotal) as gross_revenue,
        SUM(si.quantity * p.cost) as total_cost,
        SUM(si.subtotal) - SUM(si.quantity * p.cost) as gross_profit,
        SUM(s.discount) as total_discounts,
        (SUM(si.subtotal) - SUM(si.quantity * p.cost) - SUM(s.discount)) as net_profit
    FROM sale_items si
    JOIN products p ON si.productID = p.productID
    JOIN sales s ON si.saleID = s.saleID
    WHERE DATE(s.saleDate) BETWEEN startDate AND endDate
        AND s.dateDeleted IS NULL;
END //

-- Get Sales by Category
CREATE PROCEDURE GetSalesByCategory(IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT 
        COALESCE(c.categoryName, 'Uncategorized') as category,
        COUNT(DISTINCT s.saleID) as transactions,
        SUM(si.quantity) as items_sold,
        SUM(si.subtotal) as total_sales
    FROM sale_items si
    JOIN products p ON si.productID = p.productID
    JOIN sales s ON si.saleID = s.saleID
    LEFT JOIN categories c ON p.categoryID = c.categoryID
    WHERE DATE(s.saleDate) BETWEEN startDate AND endDate
        AND s.dateDeleted IS NULL
        AND p.dateDeleted IS NULL
    GROUP BY c.categoryID, c.categoryName
    ORDER BY total_sales DESC;
END //

-- Get Low Stock Products
CREATE PROCEDURE GetLowStockProducts()
BEGIN
    SELECT 
        productID,
        productCode,
        productName,
        stock,
        reorderLevel,
        price
    FROM products
    WHERE dateDeleted IS NULL
        AND stock <= reorderLevel
    ORDER BY stock ASC;
END //

-- Add Product Procedure
CREATE PROCEDURE AddProduct(
    IN p_code VARCHAR(50),
    IN p_name VARCHAR(200),
    IN p_desc TEXT,
    IN p_categoryID INT,
    IN p_price DECIMAL(10,2),
    IN p_cost DECIMAL(10,2),
    IN p_stock INT,
    IN p_reorderLevel INT
)
BEGIN
    DECLARE existing_count INT;
    
    SELECT COUNT(*) INTO existing_count FROM products WHERE productCode = p_code AND dateDeleted IS NULL;
    
    IF existing_count > 0 THEN
        SELECT 'EXIST' as status;
    ELSE
        INSERT INTO products (productCode, productName, productDesc, categoryID, price, cost, stock, reorderLevel)
        VALUES (p_code, p_name, p_desc, p_categoryID, p_price, p_cost, p_stock, p_reorderLevel);
        SELECT 'SUCCESS' as status, LAST_INSERT_ID() as productID;
    END IF;
END //

-- Update Product Procedure
CREATE PROCEDURE UpdateProduct(
    IN p_productID INT,
    IN p_code VARCHAR(50),
    IN p_name VARCHAR(200),
    IN p_desc TEXT,
    IN p_categoryID INT,
    IN p_price DECIMAL(10,2),
    IN p_cost DECIMAL(10,2),
    IN p_stock INT,
    IN p_reorderLevel INT
)
BEGIN
    DECLARE existing_count INT;
    DECLARE old_data_count INT;
    
    SELECT COUNT(*) INTO existing_count FROM products 
    WHERE productCode = p_code AND productID != p_productID AND dateDeleted IS NULL;
    
    IF existing_count > 0 THEN
        SELECT 'EXIST' as status;
    ELSE
        SELECT COUNT(*) INTO old_data_count FROM products 
        WHERE productID = p_productID 
        AND productCode = p_code 
        AND productName = p_name 
        AND price = p_price 
        AND stock = p_stock;
        
        IF old_data_count > 0 THEN
            SELECT 'NO_CHANGE' as status;
        ELSE
            UPDATE products 
            SET productCode = p_code,
                productName = p_name,
                productDesc = p_desc,
                categoryID = p_categoryID,
                price = p_price,
                cost = p_cost,
                stock = p_stock,
                reorderLevel = p_reorderLevel
            WHERE productID = p_productID;
            SELECT 'SUCCESS' as status;
        END IF;
    END IF;
END //

-- Create Sale Procedure
CREATE PROCEDURE CreateSale(
    IN p_invoiceNo VARCHAR(50),
    IN p_paymentMethod VARCHAR(20),
    IN p_amountPaid DECIMAL(12,2),
    IN p_customerName VARCHAR(100),
    IN p_customerPhone VARCHAR(20)
)
BEGIN
    DECLARE v_saleID INT;
    
    INSERT INTO sales (invoiceNo, paymentMethod, amountPaid, customerName, customerPhone)
    VALUES (p_invoiceNo, p_paymentMethod, p_amountPaid, p_customerName, p_customerPhone);
    
    SET v_saleID = LAST_INSERT_ID();
    
    SELECT v_saleID as saleID;
END //

-- Add Sale Item Procedure with Stock Update
CREATE PROCEDURE AddSaleItem(
    IN p_saleID INT,
    IN p_productID INT,
    IN p_quantity INT,
    IN p_unitPrice DECIMAL(10,2)
)
BEGIN
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_current_stock INT;
    
    SET v_subtotal = p_quantity * p_unitPrice;
    
    SELECT stock INTO v_current_stock FROM products WHERE productID = p_productID;
    
    IF v_current_stock >= p_quantity THEN
        INSERT INTO sale_items (saleID, productID, quantity, unitPrice, subtotal)
        VALUES (p_saleID, p_productID, p_quantity, p_unitPrice, v_subtotal);
        
        UPDATE products SET stock = stock - p_quantity WHERE productID = p_productID;
        
        SELECT 'SUCCESS' as status;
    ELSE
        SELECT 'INSUFFICIENT_STOCK' as status, v_current_stock as available_stock;
    END IF;
END //

-- Finalize Sale (Update totals)
CREATE PROCEDURE FinalizeSale(
    IN p_saleID INT,
    IN p_discount DECIMAL(10,2),
    IN p_tax DECIMAL(10,2)
)
BEGIN
    DECLARE v_total DECIMAL(12,2);
    DECLARE v_grandTotal DECIMAL(12,2);
    DECLARE v_changeDue DECIMAL(12,2);
    DECLARE v_amountPaid DECIMAL(12,2);
    
    SELECT SUM(subtotal) INTO v_total FROM sale_items WHERE saleID = p_saleID;
    
    SET v_grandTotal = v_total - p_discount + p_tax;
    
    SELECT amountPaid INTO v_amountPaid FROM sales WHERE saleID = p_saleID;
    SET v_changeDue = v_amountPaid - v_grandTotal;
    
    UPDATE sales 
    SET totalAmount = v_total,
        discount = p_discount,
        tax = p_tax,
        grandTotal = v_grandTotal,
        changeDue = v_changeDue
    WHERE saleID = p_saleID;
    
    SELECT 'SUCCESS' as status, v_grandTotal as grandTotal, v_changeDue as changeDue;
END //

DELIMITER ;