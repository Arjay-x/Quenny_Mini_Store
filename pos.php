<?php
// Hide all PHP warnings and errors
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Handle AJAX save draft receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_draft'])) {
    $customer_name = sanitize($conn, $_POST['customer_name'] ?? '');
    $remarks = sanitize($conn, $_POST['remarks'] ?? '');
    $items_json = $_POST['items_json'] ?? '[]';
    $total_price = floatval($_POST['total_price'] ?? 0);
    $receipt_date = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, customer_name, remarks, items_json, total_price, status) VALUES (?, ?, ?, ?, ?, 'saved')");
    $stmt->bind_param("ssssi", $receipt_date, $customer_name, $remarks, $items_json, $total_price);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Receipt saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
    exit;
}

// Handle AJAX fetch receipt by ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_receipt'])) {
    $receipt_id = intval($_POST['receipt_id']);
    
    $stmt = $conn->prepare("SELECT * FROM receipts WHERE receipt_id = ?");
    $stmt->bind_param("i", $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $receipt = $result->fetch_assoc();
        echo json_encode(['success' => true, 'receipt' => $receipt]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Receipt not found']);
    }
    $stmt->close();
    exit;
}

// Handle AJAX finalize receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_receipt'])) {
    $customer_name = sanitize($conn, $_POST['customer_name'] ?? '');
    $remarks = sanitize($conn, $_POST['remarks'] ?? '');
    $items_json = $_POST['items_json'] ?? '[]';
    $total_price = floatval($_POST['total_price'] ?? 0);
    $receipt_date = date('Y-m-d');
    
    $items_data = json_decode($items_json, true);
    
    $stmt = $conn->prepare("INSERT INTO receipts (receipt_date, customer_name, remarks, items_json, total_price, status) VALUES (?, ?, ?, ?, ?, 'completed')");
    $stmt->bind_param("ssssi", $receipt_date, $customer_name, $remarks, $items_json, $total_price);
    
    if ($stmt->execute()) {
        $receipt_id = $conn->insert_id;
        
        if (!empty($items_data)) {
            foreach ($items_data as $item) {
                $item_id = intval($item['id']);
                $quantity = intval($item['quantity']);
                
                $update_stmt = $conn->prepare("UPDATE items SET item_stocks = item_stocks - ? WHERE item_id = ?");
                $update_stmt->bind_param("ii", $quantity, $item_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        $next_receipt_id = $receipt_id + 1;
        
        echo json_encode([
            'success' => true, 
            'receipt_id' => str_pad($receipt_id, 5, '0', STR_PAD_LEFT),
            'receipt_id_raw' => $receipt_id,
            'next_receipt_id' => str_pad($next_receipt_id, 5, '0', STR_PAD_LEFT)
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    
    $stmt->close();
}

$next_receipt_num = 1;
$result = $conn->query("SELECT MAX(receipt_id) as max_id FROM receipts");
if ($result && $row = $result->fetch_assoc()) {
    $next_receipt_num = intval($row['max_id']) + 1;
}
$receipt_id_display = str_pad($next_receipt_num, 5, '0', STR_PAD_LEFT);

$items_array = [];

// Try simple query without category join first
$items_result = $conn->query("SELECT * FROM items ORDER BY item_name ASC");
if ($items_result && $items_result->num_rows > 0) {
    while ($item = $items_result->fetch_assoc()) {
        $item['category'] = 'others';
        $items_array[] = $item;
    }
}

// If still empty, use default items
if (empty($items_array)) {
    $items_array = [
        ['item_id' => 1, 'item_name' => 'Coke', 'item_price' => 25.00, 'item_stocks' => 100, 'category' => 'beverages'],
        ['item_id' => 2, 'item_name' => 'Pepsi', 'item_price' => 25.00, 'item_stocks' => 100, 'category' => 'beverages'],
        ['item_id' => 3, 'item_name' => 'Water', 'item_price' => 20.00, 'item_stocks' => 50, 'category' => 'beverages'],
        ['item_id' => 4, 'item_name' => 'Milk', 'item_price' => 55.00, 'item_stocks' => 40, 'category' => 'beverages'],
        ['item_id' => 5, 'item_name' => 'Chicken Noodles', 'item_price' => 35.00, 'item_stocks' => 80, 'category' => 'noodles'],
        ['item_id' => 6, 'item_name' => 'Beef Noodles', 'item_price' => 40.00, 'item_stocks' => 75, 'category' => 'noodles'],
        ['item_id' => 7, 'item_name' => 'Corned Beef', 'item_price' => 65.00, 'item_stocks' => 40, 'category' => 'canfoods'],
        ['item_id' => 8, 'item_name' => 'Spam', 'item_price' => 85.00, 'item_stocks' => 35, 'category' => 'canfoods'],
        ['item_id' => 9, 'item_name' => 'Bread', 'item_price' => 45.00, 'item_stocks' => 30, 'category' => 'bread'],
        ['item_id' => 10, 'item_name' => 'Chips', 'item_price' => 30.00, 'item_stocks' => 50, 'category' => 'snacks']
    ];
}

$items_json = json_encode($items_array);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quenny Store - POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f0f2f5; min-height: 100vh; }
        .navbar { position: sticky; top: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 1000; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .navbar-left { display: flex; align-items: center; gap: 15px; }
        .store-logo { width: 45px; height: 45px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .store-logo svg { width: 30px; height: 30px; fill: #667eea; }
        .store-name { color: white; font-size: 22px; font-weight: 700; letter-spacing: 1px; }
        .navbar-right { display: flex; gap: 12px; }
        .nav-btn { padding: 10px 20px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; }
        .nav-btn:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        .nav-btn svg { width: 18px; height: 18px; fill: white; }
        .main-container { display: grid; grid-template-columns: 280px 1fr 380px; gap: 20px; padding: 20px; min-height: calc(100vh - 70px); max-width: 1400px; margin: 0 auto; }
        .categories-panel { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 90px; }
        .panel-title { font-size: 16px; font-weight: 600; color: #1a1a2e; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .category-list { display: flex; flex-direction: column; gap: 8px; }
        .category-item { padding: 12px 15px; background: #f8f9fa; border: 2px solid transparent; border-radius: 10px; cursor: pointer; transition: all 0.3s ease; font-weight: 500; color: #374151; }
        .category-item:hover { background: #eef2ff; border-color: #667eea; }
        .category-item.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .items-panel { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; max-height: calc(100vh - 110px); }
        .search-bar { display: flex; gap: 10px; margin-bottom: 15px; flex-shrink: 0; }
        .items-grid-container { flex: 1; overflow-y: auto; min-height: 0; }
        .search-bar input { flex: 1; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; }
        .search-bar input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,0.1); }
        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
        .item-card { background: #f8f9fa; border: 2px solid transparent; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s ease; text-align: center; }
        .item-card:hover { border-color: #667eea; transform: translateY(-5px); box-shadow: 0 5px 20px rgba(102,126,234,0.2); }
        .item-image { width: 80px; height: 80px; background: white; border-radius: 10px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; }
        .item-image svg { width: 40px; height: 40px; fill: #9ca3af; }
        .item-name { font-size: 13px; font-weight: 600; color: #1a1a2e; margin-bottom: 5px; }
        .item-price { font-size: 16px; font-weight: 700; color: #667eea; }
        .item-stock { font-size: 11px; color: #6b7280; margin-top: 5px; }
        .item-stock.low-stock { color: #ef4444; font-weight: 600; }
        .item-stock.in-stock { color: #10b981; font-weight: 600; }
        .receipt-panel { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 90px; min-width: 380px; }
        .receipt-form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-size: 12px; font-weight: 600; color: #6b7280; white-space: nowrap; }
        .form-group input { padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; width: 100%; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .receipt-table-container { margin-bottom: 15px; overflow-x: auto; }
        .receipt-table { width: 100%; border-collapse: collapse; }
        .receipt-table th { background: #f8f9fa; padding: 10px 6px; text-align: left; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; }
        .receipt-table td { padding: 10px 6px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        .qty-cell { display: flex; align-items: center; gap: 5px; }
        .qty-btn { width: 25px; height: 25px; border: 1px solid #e5e7eb; background: white; border-radius: 5px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; color: #667eea; }
        .qty-btn:hover { background: #667eea; color: white; border-color: #667eea; }
        .qty-input { width: 40px; text-align: center; border: 1px solid #e5e7eb; border-radius: 5px; padding: 5px; font-size: 13px; }
        .delete-btn { background: #ef4444; border: none; cursor: pointer; color: white; font-size: 12px; font-weight: 600; padding: 5px 10px; border-radius: 5px; }
        .delete-btn:hover { background: #dc2626; }
        .receipt-total { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white; margin-bottom: 20px; }
        .receipt-total span:first-child { font-size: 14px; font-weight: 500; }
        .receipt-total span:last-child { font-size: 20px; font-weight: 700; }
        .receipt-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .receipt-btn { flex: 1; min-width: 80px; padding: 12px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn-save { background: #10b981; color: white; }
        .btn-save:hover { background: #059669; }
        .btn-finalize { background: #667eea; color: white; }
        .btn-finalize:hover { background: #5568d3; }
        .btn-close { background: #ef4444; color: white; }
        .btn-close:hover { background: #dc2626; }
        .btn-print { background: #f59e0b; color: white; }
        .btn-print:hover { background: #d97706; }
        .alert { padding: 12px 15px; border-radius: 10px; margin-bottom: 15px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .qty-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 3000; justify-content: center; align-items: center; }
        .qty-modal.active { display: flex; }
        .qty-modal-content { background: white; border-radius: 15px; padding: 30px; text-align: center; width: 300px; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .qty-modal-content h3 { margin-bottom: 10px; color: #1a1a2e; }
        .qty-modal-content p { color: #667eea; font-size: 24px; font-weight: 700; margin-bottom: 20px; }
        .qty-controls { display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px; }
        .qty-controls button { width: 45px; height: 45px; border: 2px solid #667eea; background: white; border-radius: 10px; font-size: 24px; font-weight: bold; color: #667eea; cursor: pointer; transition: all 0.3s ease; }
        .qty-controls button:hover { background: #667eea; color: white; }
        .qty-controls input { width: 80px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px; }
        .qty-modal-buttons { display: flex; gap: 10px; }
        .qty-modal-buttons button { flex: 1; padding: 12px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .qty-confirm { background: #667eea; color: white; }
        .qty-confirm:hover { background: #5568d3; }
        .qty-cancel { background: #e5e7eb; color: #374151; }
        .qty-cancel:hover { background: #d1d5db; }
        .empty-cart { text-align: center; padding: 40px 20px; color: #9ca3af; }
        .empty-cart svg { width: 60px; height: 60px; fill: #d1d5db; margin-bottom: 15px; }
        .empty-cart p { font-size: 14px; }
        
        /* Thermal Printer Receipt Styles */
        #printable-receipt { 
            display: none; 
            font-family: 'Courier New', monospace; 
            font-size: 12px; 
            width: 58mm; 
            padding: 5px;
        }
        
        .Print-header-center { text-align: center; margin-bottom: 10px; }
        .Print-store-name { font-size: 18px; font-weight: bold; }
        .Print-order-slip { font-size: 14px; margin-top: 5px; }
        
        .Print-info-row { display: flex; justify-content: space-between; margin: 3px 0; }
        .Print-label-bold { font-weight: bold; }
        .Print-value-right { text-align: right; }
        
        .Print-line-full { border-bottom: 1px dashed #000; margin: 5px 0; }
        
        .Print-headers { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 5px; }
        .Print-header-price { text-align: left; }
        .Print-header-qty { text-align: center; }
        .Print-header-total { text-align: right; }
        
        .Print-item { display: flex; justify-content: space-between; margin: 3px 0; }
        .Print-item-name { text-align: left; flex: 1; }
        .Print-item-qty { text-align: center; width: 40px; }
        .Print-item-price { text-align: right; width: 60px; }
        
        .Print-grand-total { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; }
        .Print-total-label { font-weight: bold; font-size: 14px; }
        .Print-total-value { text-align: right; font-weight: bold; font-size: 14px; }
        
        .Print-footer { text-align: center; margin-top: 10px; }
        .Print-generated-label { font-size: 11px; font-weight: bold; }
        .Print-generated-datetime { font-size: 11px; }
        
        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.2;
                padding: 5px;
                margin: 0;
            }
            .print-header { text-align: center; font-weight: bold; margin-bottom: 10px; }
            .print-header h1 { font-size: 18px; margin: 0; }
            .print-header h2 { font-size: 14px; margin: 5px 0; font-weight: normal; }
            .print-info { display: flex; justify-content: space-between; margin: 10px 0; }
            .print-info-left { text-align: left; }
            .print-info-right { text-align: right; }
            .print-line { border-bottom: 1px dashed #000; margin: 8px 0; }
            .print-headers { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
            .print-headers-right { text-align: right; width: 60px; }
            .print-headers-center { text-align: center; width: 40px; }
            .print-headers-left { text-align: left; }
            .print-item { display: flex; justify-content: space-between; margin: 5px 0; }
            .print-item-name { flex: 1; text-align: left; }
            .print-item-qty { text-align: center; width: 40px; }
            .print-item-price { text-align: right; width: 60px; }
            .print-total-right { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin: 10px 0; }
            .print-total-right { text-align: right; flex: 1; }
            .print-total-right { text-align: right; width: 80px; }
            .print-footer { text-align: center; margin-top: 15px; font-size: 11px; }
            @page { margin: 0; size: 80mm auto; }
        }
        
        @media (max-width: 992px) { .main-container { grid-template-columns: 1fr; } .categories-panel, .receipt-panel { position: static; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <div class="store-logo">
                <svg viewBox="0 0 24 24"><path d="M17 2H7C5.9 2 5 2.9 5 4V20C5 21.1 5.9 22 7 22H17C18.1 22 19 21.1 19 20V4C19 2.9 18.1 2 17 2ZM7 20V4H17V20H7ZM9 6H15V8H9V6ZM9 10H15V12H9V10ZM9 14H15V16H9V14ZM9 18H15V20H9V18Z"/></svg>
            </div>
            <span class="store-name">Quenny Store</span>
        </div>
        <div class="navbar-right">
            <button class="nav-btn" onclick="logout()">
                <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)
            </button>
        </div>
    </nav>

    <div class="main-container">
        <div class="categories-panel">
            <h3 class="panel-title">Categories</h3>
            <div class="category-list" id="categoryList">
                <div class="category-item active" onclick="selectCategory('allproducts')">All Products</div>
                <div class="category-item" onclick="selectCategory('noodles')">Noodles</div>
                <div class="category-item" onclick="selectCategory('canfoods')">Can Foods</div>
                <div class="category-item" onclick="selectCategory('beverages')">Beverages</div>
                <div class="category-item" onclick="selectCategory('bread')">Bread</div>
                <div class="category-item" onclick="selectCategory('snacks')">Snacks</div>
                <div class="category-item" onclick="selectCategory('others')">Others</div>
            </div>
        </div>

        <div class="items-panel">
            <div class="search-bar">
                <input type="text" placeholder="Search items..." id="itemSearch" onkeyup="searchItems()">
            </div>
            <div class="items-grid-container">
                <div class="items-grid" id="itemsGrid"></div>
            </div>
        </div>

        <div class="receipt-panel">
            <h3 class="panel-title">Receipt</h3>
            <div id="successMessage" class="alert alert-success" style="display: none;"></div>
            <div id="errorMessage" class="alert alert-error" style="display: none;"></div>
            
            <div class="receipt-form">
                <div class="form-group">
                    <label>Receipt ID</label>
                    <input type="text" id="receiptId" value="<?php echo $receipt_id_display; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="text" value="<?php echo date('M d, Y'); ?>" readonly>
                </div>
                <div class="form-group full-width">
                    <label>Customer Name *</label>
                    <input type="text" id="customerName" placeholder="Enter customer name" required>
                </div>
                <div class="form-group full-width">
                    <label>Remarks (Optional)</label>
                    <input type="text" id="remarks" placeholder="Any notes...">
                </div>
            </div>
            <div class="receipt-table-container">
                <table class="receipt-table">
                    <thead>
                        <tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th><th>Remove</th></tr>
                    </thead>
                    <tbody id="receiptTableBody"></tbody>
                </table>
            </div>
            <div class="empty-cart" id="emptyCart">
                <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                <p>No items added yet</p>
            </div>
            <div class="receipt-total" id="totalSection" style="display: none;">
                <span>Total Amount:</span>
                <span id="totalAmount">₱0.00</span>
            </div>
            <div class="receipt-buttons">
                <button type="button" class="receipt-btn btn-save" onclick="saveReceipt()">Save</button>
                <button type="button" class="receipt-btn btn-finalize" onclick="finalizeReceipt()">Finalize</button>
                <button type="button" class="receipt-btn btn-print" onclick="printReceipt()">Print</button>
                <button type="button" class="receipt-btn btn-close" onclick="closeReceipt()">Close</button>
            </div>
        </div>
    </div>

    <div class="qty-modal" id="qtyModal">
        <div class="qty-modal-content">
            <h3 id="qtyItemName">Item Name</h3>
            <p id="qtyItemPrice">₱0.00</p>
            <div class="qty-controls">
                <button onclick="decreaseQty()">-</button>
                <input type="number" id="qtyInput" value="1" min="1">
                <button onclick="increaseQty()">+</button>
            </div>
            <div class="qty-modal-buttons">
                <button class="qty-cancel" onclick="closeQtyModal()">Cancel</button>
                <button class="qty-confirm" onclick="confirmQty()">Add to Receipt</button>
            </div>
        </div>
    </div>

    <!-- Thermal Printer Receipt Template -->
    <div id="printable-receipt">
        <div class="print-header-center">
            <div class="print-store-name" style="font-size:18px;font-weight:bold;">QUEENNY STORE</div>
            <div class="print-order-slip" style="font-size:14px;">Order Slip</div>
        </div>
        
        <div class="print-line-full" style="border-bottom:1px dashed #000;margin:5px 0;"></div>
        
        <div class="print-info-row">
            <span class="print-label-bold">Date:</span>
            <span class="print-value-right" id="print-date"></span>
        </div>
        
        <div class="print-info-row">
            <span class="print-label-bold">Customer:</span>
            <span class="print-value-right" id="print-customer"></span>
        </div>
        
        <div class="print-info-row">
            <span class="print-label-bold">Receipt #:</span>
            <span class="print-value-right" id="print-receipt-id"></span>
        </div>
        
        <div id="print-remarks-section" style="display:none;">
            <div class="print-info-row">
                <span class="print-label-bold">Remarks:</span>
                <span class="print-value-right" id="print-remarks"></span>
            </div>
        </div>
        
        <div class="print-line-full" style="border-bottom:1px dashed #000;margin:5px 0;"></div>
        
        <div class="print-headers">
            <span class="print-header-price" style="font-weight:bold;">PRICE</span>
            <span class="print-header-qty" style="font-weight:bold;">QTY</span>
            <span class="print-header-total" style="font-weight:bold;">TOTAL</span>
        </div>
        
        
        <div id="print-items"></div>
        
        <div class="print-line-full" style="border-bottom:1px dashed #000;margin:5px 0;"></div>
        
        <div class="print-grand-total">
            <span class="print-total-label" style="font-weight:bold;font-size:14px;">TOTAL AMOUNT:</span>
            <span class="print-total-value" style="font-weight:bold;font-size:14px;" id="print-total-amount"></span>
        </div>
        
        <div class="print-line-full" style="border-bottom:1px dashed #000;margin:5px 0;"></div>
        
        <div class="print-footer">
            <div class="print-generated-label" style="font-size:11px;font-weight:bold;">Generated:</div>
            <div class="print-generated-datetime" style="font-size:11px;">
                <span id="print-generated-date"></span> 
                <span id="print-generated-time"></span>
            </div>
        </div>
    </div>

    <script>
        let itemsData = <?php echo $items_json; ?>;
        
        if (!itemsData || itemsData.length === 0) {
            itemsData = [
                {item_id: 1, item_name: 'Coke', item_price: 25.00, item_stocks: 100, category: 'beverages'},
                {item_id: 2, item_name: 'Pepsi', item_price: 25.00, item_stocks: 100, category: 'beverages'},
                {item_id: 3, item_name: 'Water', item_price: 20.00, item_stocks: 50, category: 'beverages'},
                {item_id: 4, item_name: 'Milk', item_price: 55.00, item_stocks: 40, category: 'beverages'},
                {item_id: 5, item_name: 'Chicken Noodles', item_price: 35.00, item_stocks: 80, category: 'noodles'},
                {item_id: 6, item_name: 'Beef Noodles', item_price: 40.00, item_stocks: 75, category: 'noodles'},
                {item_id: 7, item_name: 'Pancit Canton', item_price: 30.00, item_stocks: 60, category: 'noodles'},
                {item_id: 8, item_name: 'Lucky Me Noodles', item_price: 15.00, item_stocks: 100, category: 'noodles'},
                {item_id: 9, item_name: 'Corned Beef', item_price: 65.00, item_stocks: 40, category: 'canfoods'},
                {item_id: 10, item_name: 'Spam', item_price: 85.00, item_stocks: 35, category: 'canfoods'},
                {item_id: 11, item_name: 'Sardines', item_price: 35.00, item_stocks: 50, category: 'canfoods'},
                {item_id: 12, item_name: 'Tuna', item_price: 55.00, item_stocks: 45, category: 'canfoods'},
                {item_id: 13, item_name: 'Bread', item_price: 45.00, item_stocks: 30, category: 'bread'},
                {item_id: 14, item_name: 'Pandesal', item_price: 2.00, item_stocks: 100, category: 'bread'},
                {item_id: 15, item_name: 'Loaf Bread', item_price: 60.00, item_stocks: 25, category: 'bread'},
                {item_id: 16, item_name: 'Chips', item_price: 30.00, item_stocks: 50, category: 'snacks'},
                {item_id: 17, item_name: 'Cookies', item_price: 25.00, item_stocks: 40, category: 'snacks'},
                {item_id: 18, item_name: 'Chocolate Bar', item_price: 20.00, item_stocks: 60, category: 'snacks'}
            ];
        }

        let cart = [];
        let currentItem = null;
        let currentCategory = 'allproducts';
        let currentReceiptId = '<?php echo $receipt_id_display; ?>';
        let lastSavedReceiptId = null;
        let pendingNewReceipt = false;

        document.addEventListener('DOMContentLoaded', function() {
            renderItems();
        });

        function getStockClass(stock) {
            if (stock <= 10) return 'low-stock';
            return 'in-stock';
        }

        function renderItems() {
            const grid = document.getElementById('itemsGrid');
            const searchTerm = document.getElementById('itemSearch').value.toLowerCase();
            let filteredItems = itemsData;
            if (currentCategory !== 'allproducts') filteredItems = filteredItems.filter(item => item.category === currentCategory);
            if (searchTerm) filteredItems = filteredItems.filter(item => item.item_name.toLowerCase().includes(searchTerm));
            grid.innerHTML = filteredItems.map(item => `
                <div class="item-card" onclick="openQtyModal(${item.item_id})">
                    <div class="item-image"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg></div>
                    <div class="item-name">${item.item_name}</div>
                    <div class="item-price">₱${parseFloat(item.item_price).toFixed(2)}</div>
                    <div class="item-stock ${getStockClass(item.item_stocks)}">Stock: ${item.item_stocks}</div>
                </div>
            `).join('');
        }

        function selectCategory(category) {
            currentCategory = category;
            document.querySelectorAll('.category-item').forEach(item => item.classList.remove('active'));
            event.target.classList.add('active');
            renderItems();
        }

        function searchItems() { renderItems(); }

        function openQtyModal(itemId) {
            itemId = parseInt(itemId);
            currentItem = itemsData.find(item => parseInt(item.item_id) === itemId);
            if (!currentItem) { alert('Item not found!'); return; }
            document.getElementById('qtyItemName').textContent = currentItem.item_name;
            document.getElementById('qtyItemPrice').textContent = '₱' + parseFloat(currentItem.item_price).toFixed(2);
            document.getElementById('qtyInput').value = 1;
            document.getElementById('qtyModal').classList.add('active');
        }

        function closeQtyModal() { document.getElementById('qtyModal').classList.remove('active'); currentItem = null; }
        function increaseQty() { document.getElementById('qtyInput').value = parseInt(document.getElementById('qtyInput').value) + 1; }
        function decreaseQty() { const input = document.getElementById('qtyInput'); if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1; }

        function confirmQty() {
            const qty = parseInt(document.getElementById('qtyInput').value);
            const existingItem = cart.find(item => item.id === currentItem.item_id);
            if (existingItem) { existingItem.quantity += qty; existingItem.total = existingItem.quantity * existingItem.price; }
            else { cart.push({ id: currentItem.item_id, name: currentItem.item_name, price: parseFloat(currentItem.item_price), quantity: qty, total: qty * parseFloat(currentItem.item_price) }); }
            updateReceipt();
            closeQtyModal();
        }

        function updateReceipt() {
            const tbody = document.getElementById('receiptTableBody');
            const emptyCart = document.getElementById('emptyCart');
            const totalSection = document.getElementById('totalSection');
            const totalAmount = document.getElementById('totalAmount');
            if (cart.length === 0) { tbody.innerHTML = ''; emptyCart.style.display = 'block'; totalSection.style.display = 'none'; return; }
            emptyCart.style.display = 'none'; totalSection.style.display = 'flex';
            tbody.innerHTML = cart.map((item, index) => `
                <tr><td>${item.name}</td><td>₱${item.price.toFixed(2)}</td><td><div class="qty-cell"><button class="qty-btn" onclick="changeQuantity(${index}, -1)">-</button><input type="number" class="qty-input" value="${item.quantity}" min="1" onchange="setQuantity(${index}, this.value)"><button class="qty-btn" onclick="changeQuantity(${index}, 1)">+</button></div></td><td>₱${item.total.toFixed(2)}</td><td><button class="delete-btn" onclick="removeItem(${index})">&times;</button></td></tr>
            `).join('');
            const grandTotal = cart.reduce((sum, item) => sum + item.total, 0);
            totalAmount.textContent = '₱' + grandTotal.toFixed(2);
        }

        function changeQuantity(index, change) { cart[index].quantity += change; if (cart[index].quantity < 1) cart[index].quantity = 1; cart[index].total = cart[index].quantity * cart[index].price; updateReceipt(); }
        function setQuantity(index, value) { const qty = parseInt(value); cart[index].quantity = qty < 1 ? 1 : qty; cart[index].total = cart[index].quantity * cart[index].price; updateReceipt(); }
        function removeItem(index) { cart.splice(index, 1); updateReceipt(); }

        function saveReceipt() {
            if (cart.length === 0) { alert('No items added to the receipt!'); return; }
            const customerName = document.getElementById('customerName').value.trim();
            if (!customerName) { alert('Please enter customer name'); return; }
            
            const totalPrice = cart.reduce((sum, item) => sum + item.total, 0);
            const formData = new FormData();
            formData.append('save_draft', '1');
            formData.append('customer_name', customerName);
            formData.append('remarks', document.getElementById('remarks').value);
            formData.append('items_json', JSON.stringify(cart));
            formData.append('total_price', totalPrice);
            
            fetch('pos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Receipt saved to list! You can continue adding items or finalize the receipt.');
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving receipt. Please try again.');
            });
        }

        function startNewReceipt() {
            pendingNewReceipt = true;
            cart = [];
            updateReceipt();
            document.getElementById('customerName').value = '';
            document.getElementById('remarks').value = '';
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
        }

        function closeReceipt() {
            if (cart.length > 0 && !confirm('Are you sure you want to clear the receipt?')) return;
            cart = [];
            updateReceipt();
            document.getElementById('customerName').value = '';
            document.getElementById('remarks').value = '';
            lastSavedReceiptId = null;
            pendingNewReceipt = false;
        }

function logout() { if (confirm('Are you sure you want to logout?')) window.location.href = 'login.php'; }

        function preparePrintReceiptFromDatabase(receiptId, callback) {
            const formData = new FormData();
            formData.append('fetch_receipt', '1');
            formData.append('receipt_id', receiptId);
            
            fetch('pos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const receipt = data.receipt;
                    const items = JSON.parse(receipt.items_json);
                    
                    document.getElementById('print-date').textContent = receipt.receipt_date;
                    document.getElementById('print-customer').textContent = receipt.customer_name;
                    document.getElementById('print-receipt-id').textContent = String(receipt.receipt_id).padStart(5, '0');
                    document.getElementById('print-total-amount').textContent = '₱' + parseFloat(receipt.total_price).toFixed(2);
                    
                    if (receipt.remarks) {
                        document.getElementById('print-remarks-section').style.display = 'block';
                        document.getElementById('print-remarks').textContent = receipt.remarks;
                    } else {
                        document.getElementById('print-remarks-section').style.display = 'none';
                    }
                    
                    let itemsHtml = '';
                    items.forEach(item => {
                        itemsHtml += `
                        <div class="Print-item">
                            <div class="Print-item-name">${item.name}</div>
                            <div class="Print-item-qty">${item.quantity}</div>
                            <div class="Print-item-price">₱${item.total.toFixed(2)}</div>
                        </div>`;
                    });
                    document.getElementById('print-items').innerHTML = itemsHtml;
                    
                    document.getElementById('print-generated-date').textContent = receipt.receipt_date;
                    document.getElementById('print-generated-time').textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    
                    window.print();
                    
                    if (callback) callback();
                } else {
                    alert('Error fetching receipt: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching receipt data');
            });
        }

        function preparePrintReceipt() {
            const customerName = document.getElementById('customerName').value.trim() || 'Guest';
            const remarks = document.getElementById('remarks').value.trim();
            const receiptId = document.getElementById('receiptId').value;
            const total = cart.reduce((sum, item) => sum + item.total, 0);
            
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            
            document.getElementById('print-date').textContent = dateStr;
            document.getElementById('print-customer').textContent = customerName;
            document.getElementById('print-receipt-id').textContent = receiptId;
            document.getElementById('print-total-amount').textContent = '₱' + total.toFixed(2);
            document.getElementById('print-generated-date').textContent = dateStr;
            document.getElementById('print-generated-time').textContent = timeStr;
            
            if (remarks) {
                document.getElementById('print-remarks-section').style.display = 'block';
                document.getElementById('print-remarks').textContent = remarks;
            } else {
                document.getElementById('print-remarks-section').style.display = 'none';
            }
            
            let itemsHtml = '';
            cart.forEach(item => {
                itemsHtml += `
                <div class="Print-item">
                    <div class="Print-item-name">${item.name}</div>
                    <div class="Print-item-qty">${item.quantity}</div>
                    <div class="Print-item-price">₱${item.total.toFixed(2)}</div>
                </div>`;
            });
            document.getElementById('print-items').innerHTML = itemsHtml;
        }

        function printReceipt() {
            if (cart.length === 0 && !lastSavedReceiptId) { alert('No items to print!'); return; }
            
            if (lastSavedReceiptId) {
                showPrintAgainLoop(lastSavedReceiptId);
            } else if (cart.length > 0) {
                preparePrintReceipt();
                window.print();
            }
        }

        function showPrintAgainLoop(receiptId) {
            preparePrintReceiptFromDatabase(receiptId, function() {
                setTimeout(function() {
                    if (confirm('Print again?')) {
                        preparePrintReceiptFromDatabase(receiptId, function() {
                            setTimeout(function() {
                                if (confirm('Print again?')) {
                                    preparePrintReceiptFromDatabase(receiptId);
                                }
                            }, 300);
                        });
                    }
                }, 500);
            });
        }

        function showPrintPopup() {
            if (lastSavedReceiptId) {
                showPrintAgainLoop(lastSavedReceiptId);
            } else {
                preparePrintReceipt();
                if (confirm('Do you want to print the receipt?')) {
                    window.print();
                }
            }
        }

        function finalizeReceipt() {
            if (cart.length === 0) { alert('No items added to the receipt! Please add items first.'); return; }
            const customerName = document.getElementById('customerName').value.trim();
            if (!customerName) { alert('Please enter customer name'); return; }
            
            if (!confirm('Do you want to finalize this receipt?')) {
                return;
            }
            
            const totalPrice = cart.reduce((sum, item) => sum + item.total, 0);
            const formData = new FormData();
            formData.append('finalize_receipt', '1');
            formData.append('customer_name', customerName);
            formData.append('remarks', document.getElementById('remarks').value);
            formData.append('items_json', JSON.stringify(cart));
            formData.append('total_price', totalPrice);
            
            fetch('pos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    lastSavedReceiptId = data.receipt_id_raw;
                    
                    document.getElementById('successMessage').textContent = 'Receipt finalized successfully! Receipt ID: ' + data.receipt_id;
                    document.getElementById('successMessage').style.display = 'block';
                    document.getElementById('errorMessage').style.display = 'none';
                    
                    document.getElementById('receiptId').value = data.next_receipt_id;
                    
                    cart = [];
                    updateReceipt();
                    document.getElementById('customerName').value = '';
                    document.getElementById('remarks').value = '';
                    
                    showPrintPopup();
                } else {
                    document.getElementById('errorMessage').textContent = 'Error: ' + data.error;
                    document.getElementById('errorMessage').style.display = 'block';
                    document.getElementById('successMessage').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving receipt. Please try again.');
            });
        }
    </script>
    
    <!-- Google Adsense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2862802439514164"
     crossorigin="anonymous"></script>
</body>
</html>
