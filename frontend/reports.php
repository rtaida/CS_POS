<?php
require_once '../backend/database.php';
session_start();

if(!isset($_SESSION['userID'])){
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CS POS - Reports</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
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

.report-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.report-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.report-header h4 {
    font-weight: 600;
}

.filter-group {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 20px;
}

.table-responsive-custom {
    max-height: 400px;
    overflow-y: auto;
}

.nav-tabs .nav-link {
    border-radius: 10px;
    margin-right: 5px;
    padding: 10px 20px;
}

.nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.nav-tabs .nav-link:not(.active) {
    color: #666;
}

.nav-tabs .nav-link:not(.active):hover {
    border-color: #e0e0e0;
    background: #f5f5f5;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

.summary-card {
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
}
</style>
</head>
<body>

<?php include "nav.php"; ?>

<div class="main-content">
    <div class="report-card">
        <div class="report-header">
            <h4><i class="bi bi-graph-up"></i> Ad-hoc Reports</h4>
            <p class="text-muted mb-0">Generate custom reports and analyze your business performance</p>
        </div>
        
        <!-- Report Type Selector -->
        <ul class="nav nav-tabs mb-4" id="reportTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#salesSummary" type="button">
                    <i class="bi bi-bar-chart"></i> Sales Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#topProducts" type="button">
                    <i class="bi bi-trophy"></i> Top Products
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventory" type="button">
                    <i class="bi bi-boxes"></i> Inventory Status
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profitLoss" type="button">
                    <i class="bi bi-calculator"></i> Profit & Loss
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#categorySales" type="button">
                    <i class="bi bi-pie-chart"></i> Sales by Category
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Sales Summary Report -->
            <div class="tab-pane fade show active" id="salesSummary">
                <div class="filter-group">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="ss_startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="ss_endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="loadSalesSummary()">
                                <i class="bi bi-search"></i> Generate Report
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" onclick="exportReport('sales_summary')">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
                <div id="salesSummaryResult">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-range" style="font-size: 48px;"></i>
                        <p class="mt-2">Select date range and click Generate Report</p>
                    </div>
                </div>
            </div>
            
            <!-- Top Products Report -->
            <div class="tab-pane fade" id="topProducts">
                <div class="filter-group">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="tp_startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="tp_endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Limit</label>
                            <input type="number" id="tp_limit" class="form-control" value="10" min="1" max="50">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="loadTopProducts()">
                                <i class="bi bi-search"></i> Generate
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" onclick="exportReport('top_products')">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div id="topProductsResult">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-trophy" style="font-size: 48px;"></i>
                        <p class="mt-2">Select date range and click Generate Report</p>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Status Report -->
            <div class="tab-pane fade" id="inventory">
                <div class="filter-group">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="loadInventoryStatus()">
                                <i class="bi bi-search"></i> Generate Inventory Report
                            </button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" onclick="exportReport('inventory')">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
                <div id="inventoryResult">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-boxes" style="font-size: 48px;"></i>
                        <p class="mt-2">Click Generate Inventory Report</p>
                    </div>
                </div>
            </div>
            
            <!-- Profit & Loss Report -->
            <div class="tab-pane fade" id="profitLoss">
                <div class="filter-group">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="pl_startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="pl_endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="loadProfitLoss()">
                                <i class="bi bi-search"></i> Generate
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" onclick="exportReport('profit_loss')">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div id="profitLossResult">
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calculator" style="font-size: 48px;"></i>
                        <p class="mt-2">Select date range and click Generate Report</p>
                    </div>
                </div>
            </div>
            
            <!-- Sales by Category Report -->
            <div class="tab-pane fade" id="categorySales">
                <div class="filter-group">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="cs_startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="cs_endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary w-100" onclick="loadCategorySales()">
                                <i class="bi bi-search"></i> Generate
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-success w-100" onclick="exportReport('category_sales')">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="categoryChart" height="300"></canvas>
                    </div>
                    <div class="col-md-6">
                        <div id="categorySalesResult"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let categoryChart = null;

function loadSalesSummary() {
    let startDate = $('#ss_startDate').val();
    let endDate = $('#ss_endDate').val();
    
    if(!startDate || !endDate) {
        Swal.fire('Error', 'Please select both start and end dates', 'error');
        return;
    }
    
    $('#salesSummaryResult').html('<div class="text-center py-5"><i class="bi bi-hourglass-split"></i> Loading...</div>');
    
    $.ajax({
        url: '../backend/reportAuth.php',
        method: 'POST',
        data: { getSalesSummary: true, startDate: startDate, endDate: endDate },
        dataType: 'json',
        success: function(sales) {
            if(sales && sales.length > 0) {
                let html = `
                    <div class="table-responsive-custom">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Transactions</th>
                                    <th>Total Sales</th>
                                    <th>Average</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                sales.forEach(s => {
                    html += `<tr>
                        <td>${s.sale_date || s.date || '-'}</td>
                        <td>${s.transaction_count || 0}</td>
                        <td class="text-success">₱${parseFloat(s.total_sales || 0).toFixed(2)}</td>
                        <td>₱${parseFloat(s.average_transaction || 0).toFixed(2)}</td>
                        <td>₱${parseFloat(s.total_discount || 0).toFixed(2)}</td>
                        <td>₱${parseFloat(s.total_tax || 0).toFixed(2)}</td>
                    </tr>`;
                });
                html += `</tbody></table></div>`;
                $('#salesSummaryResult').html(html);
            } else {
                $('#salesSummaryResult').html('<div class="alert alert-info">No sales data found for the selected period.</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            $('#salesSummaryResult').html('<div class="alert alert-danger">Error loading report. Please try again.</div>');
        }
    });
}

function loadTopProducts() {
    let startDate = $('#tp_startDate').val();
    let endDate = $('#tp_endDate').val();
    let limit = $('#tp_limit').val();
    
    $('#topProductsResult').html('<div class="text-center py-5"><i class="bi bi-hourglass-split"></i> Loading...</div>');
    
    $.ajax({
        url: '../backend/reportAuth.php',
        method: 'POST',
        data: { getTopProducts: true, startDate: startDate, endDate: endDate, limit: limit },
        dataType: 'json',
        success: function(products) {
            if(products && products.length > 0) {
                let html = `
                    <div class="table-responsive-custom">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                products.forEach((p, idx) => {
                    html += `<tr>
                        <td><span class="badge bg-primary rounded-pill">#${idx+1}</span></td>
                        <td><strong>${p.productName}</strong><br><small class="text-muted">${p.productCode}</small></td>
                        <td>${p.categoryName || 'Uncategorized'}</td>
                        <td>${p.total_quantity_sold || 0}</td>
                        <td class="text-success">₱${parseFloat(p.total_revenue || 0).toFixed(2)}</td>
                    </tr>`;
                });
                html += `</tbody></table></div>`;
                $('#topProductsResult').html(html);
            } else {
                $('#topProductsResult').html('<div class="alert alert-info">No product sales found for the selected period.</div>');
            }
        },
        error: function() {
            $('#topProductsResult').html('<div class="alert alert-danger">Error loading report. Please try again.</div>');
        }
    });
}

function loadInventoryStatus() {
    $('#inventoryResult').html('<div class="text-center py-5"><i class="bi bi-hourglass-split"></i> Loading...</div>');
    
    $.ajax({
        url: '../backend/reportAuth.php',
        method: 'POST',
        data: { getInventoryStatus: true },
        dataType: 'json',
        success: function(products) {
            if(products && products.length > 0) {
                let html = `
                    <div class="table-responsive-custom">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                products.forEach(p => {
                    let statusClass = p.stock_status === 'Out of Stock' ? 'text-danger' : (p.stock_status === 'Low Stock' ? 'text-warning' : 'text-success');
                    html += `<tr>
                        <td><code>${p.productCode}</code></td>
                        <td><strong>${p.productName}</strong></td>
                        <td>${p.categoryName || '-'}</td>
                        <td>${p.stock || 0}</td>
                        <td>${p.reorderLevel || 0}</td>
                        <td class="${statusClass}">${p.stock_status || 'Unknown'}</td>
                        <td>₱${parseFloat(p.price || 0).toFixed(2)}</td>
                    </tr>`;
                });
                html += `</tbody></table></div>`;
                $('#inventoryResult').html(html);
            } else {
                $('#inventoryResult').html('<div class="alert alert-info">No products found.</div>');
            }
        },
        error: function() {
            $('#inventoryResult').html('<div class="alert alert-danger">Error loading inventory. Please try again.</div>');
        }
    });
}

function loadProfitLoss() {
    let startDate = $('#pl_startDate').val();
    let endDate = $('#pl_endDate').val();
    
    $('#profitLossResult').html('<div class="text-center py-5"><i class="bi bi-hourglass-split"></i> Loading...</div>');
    
    $.ajax({
        url: '../backend/reportAuth.php',
        method: 'POST',
        data: { getProfitLoss: true, startDate: startDate, endDate: endDate },
        dataType: 'json',
        success: function(pl) {
            let grossRevenue = parseFloat(pl.gross_revenue || 0);
            let totalCost = parseFloat(pl.total_cost || 0);
            let grossProfit = parseFloat(pl.gross_profit || 0);
            let netProfit = parseFloat(pl.net_profit || 0);
            let profitMargin = grossRevenue > 0 ? (netProfit / grossRevenue * 100) : 0;
            
            let html = `
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="summary-card">
                            <small class="text-muted">Gross Revenue</small>
                            <h4 class="text-primary">₱${grossRevenue.toFixed(2)}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <small class="text-muted">Total Cost</small>
                            <h4 class="text-danger">₱${totalCost.toFixed(2)}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <small class="text-muted">Gross Profit</small>
                            <h4 class="text-success">₱${grossProfit.toFixed(2)}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <small class="text-muted">Net Profit</small>
                            <h4 class="text-success">₱${netProfit.toFixed(2)}</h4>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Total Discounts:</strong> ₱${parseFloat(pl.total_discounts || 0).toFixed(2)}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Profit Margin:</strong> ${profitMargin.toFixed(2)}%
                        </div>
                    </div>
                </div>
            `;
            $('#profitLossResult').html(html);
        },
        error: function() {
            $('#profitLossResult').html('<div class="alert alert-danger">Error loading profit/loss data. Please try again.</div>');
        }
    });
}

function loadCategorySales() {
    let startDate = $('#cs_startDate').val();
    let endDate = $('#cs_endDate').val();
    
    $('#categorySalesResult').html('<div class="text-center py-5"><i class="bi bi-hourglass-split"></i> Loading...</div>');
    
    $.ajax({
        url: '../backend/reportAuth.php',
        method: 'POST',
        data: { getSalesByCategory: true, startDate: startDate, endDate: endDate },
        dataType: 'json',
        success: function(categories) {
            if(categories && categories.length > 0) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Transactions</th>
                                    <th>Items Sold</th>
                                    <th>Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                let labels = [];
                let values = [];
                categories.forEach(c => {
                    html += `<tr>
                        <td><strong>${c.category}</strong></td>
                        <td>${c.transactions || 0}</td>
                        <td>${c.items_sold || 0}</td>
                        <td class="text-success">₱${parseFloat(c.total_sales || 0).toFixed(2)}</td>
                    </tr>`;
                    labels.push(c.category);
                    values.push(parseFloat(c.total_sales || 0));
                });
                html += `</tbody></table></div>`;
                $('#categorySalesResult').html(html);
                
                // Update chart
                if(categoryChart) categoryChart.destroy();
                let ctx = document.getElementById('categoryChart').getContext('2d');
                categoryChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: { 
                        labels: labels, 
                        datasets: [{ 
                            data: values, 
                            backgroundColor: ['#667eea', '#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6f42c1', '#fd7e14', '#20c997'] 
                        }] 
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ₱${ctx.raw.toFixed(2)}` } }
                        }
                    }
                });
            } else {
                $('#categorySalesResult').html('<div class="alert alert-info">No data found for the selected period.</div>');
                if(categoryChart) categoryChart.destroy();
            }
        },
        error: function() {
            $('#categorySalesResult').html('<div class="alert alert-danger">Error loading category sales. Please try again.</div>');
        }
    });
}

function exportReport(type) {
    let params = new URLSearchParams();
    params.append('exportReport', true);
    params.append('reportType', type);
    
    if(type === 'sales_summary') {
        params.append('startDate', $('#ss_startDate').val());
        params.append('endDate', $('#ss_endDate').val());
    } else if(type === 'top_products') {
        params.append('startDate', $('#tp_startDate').val());
        params.append('endDate', $('#tp_endDate').val());
        params.append('limit', $('#tp_limit').val());
    } else if(type === 'profit_loss') {
        params.append('startDate', $('#pl_startDate').val());
        params.append('endDate', $('#pl_endDate').val());
    } else if(type === 'category_sales') {
        params.append('startDate', $('#cs_startDate').val());
        params.append('endDate', $('#cs_endDate').val());
    } else if(type === 'inventory') {
        // No additional params needed
    }
    
    window.location.href = '../backend/reportAuth.php?' + params.toString();
}

// Load default reports on page load
$(document).ready(function() {
    loadSalesSummary();
    loadInventoryStatus();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>