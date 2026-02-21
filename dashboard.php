<?php
require_once 'db.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Handle add item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $item_name = sanitize($conn, $_POST['item_name']);
    $item_price = sanitize($conn, $_POST['item_price']);
    $item_stocks = sanitize($conn, $_POST['item_stocks']);
    
    $stmt = $conn->prepare("INSERT INTO items (item_name, item_price, item_stocks) VALUES (?, ?, ?)");
    $stmt->bind_param("sdi", $item_name, $item_price, $item_stocks);
    
    if ($stmt->execute()) {
        $success = "Item added successfully!";
    } else {
        $error = "Error adding item";
    }
}

// Handle delete item via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item_ajax'])) {
    $item_id = sanitize($conn, $_POST['item_id']);
    
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error deleting item']);
    }
    $stmt->close();
    exit;
}

// Handle delete receipt via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_receipt_ajax'])) {
    $receipt_id = sanitize($conn, $_POST['receipt_id']);
    
    // Just delete the receipt - NO changes to item stocks or sales
    $stmt = $conn->prepare("DELETE FROM receipts WHERE receipt_id = ?");
    $stmt->bind_param("i", $receipt_id);
    
    if ($stmt->execute()) {
        // Reset AUTO_INCREMENT to make next receipt use the deleted ID
        $max_result = $conn->query("SELECT MAX(receipt_id) as max_id FROM receipts");
        $max_row = $max_result->fetch_assoc();
        $max_id = $max_row['max_id'] ?? 0;
        
        // Set AUTO_INCREMENT to max_id + 1 so next receipt uses next available ID
        $new_auto_increment = $max_id + 1;
        $conn->query("ALTER TABLE receipts AUTO_INCREMENT = $new_auto_increment");
        
        echo json_encode(['success' => true, 'message' => 'Receipt deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error deleting receipt']);
    }
    $stmt->close();
    exit;
}

// Handle delete item (regular)
if (isset($_GET['delete_item'])) {
    $item_id = sanitize($conn, $_GET['delete_item']);
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    redirect('dashboard.php');
}

// Handle update stocks via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stocks_ajax'])) {
    $item_id = sanitize($conn, $_POST['item_id']);
    $item_stocks = sanitize($conn, $_POST['item_stocks']);
    
    $stmt = $conn->prepare("UPDATE items SET item_stocks = ? WHERE item_id = ?");
    $stmt->bind_param("ii", $item_stocks, $item_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Stocks updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error updating stocks']);
    }
    $stmt->close();
    exit;
}

// Handle update item via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item_ajax'])) {
    $item_id = sanitize($conn, $_POST['item_id']);
    $item_name = sanitize($conn, $_POST['item_name']);
    $item_price = sanitize($conn, $_POST['item_price']);
    
    $stmt = $conn->prepare("UPDATE items SET item_name = ?, item_price = ? WHERE item_id = ?");
    $stmt->bind_param("sdi", $item_name, $item_price, $item_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error updating item']);
    }
    $stmt->close();
    exit;
}

// Get all items
$items_result = $conn->query("SELECT * FROM items ORDER BY item_id DESC");
$items_list = [];
while ($row = $items_result->fetch_assoc()) {
    $items_list[] = $row;
}
if (empty($items_list)) {
    $items_list = [
        ['item_id' => 1, 'item_name' => 'Coke', 'item_price' => 25.00, 'item_stocks' => 100],
        ['item_id' => 2, 'item_name' => 'Pepsi', 'item_price' => 25.00, 'item_stocks' => 100],
        ['item_id' => 3, 'item_name' => 'Water', 'item_price' => 20.00, 'item_stocks' => 50],
        ['item_id' => 4, 'item_name' => 'Milk', 'item_price' => 55.00, 'item_stocks' => 40],
        ['item_id' => 5, 'item_name' => 'Chicken Noodles', 'item_price' => 35.00, 'item_stocks' => 80],
        ['item_id' => 6, 'item_name' => 'Beef Noodles', 'item_price' => 40.00, 'item_stocks' => 75],
        ['item_id' => 7, 'item_name' => 'Pancit Canton', 'item_price' => 30.00, 'item_stocks' => 60],
        ['item_id' => 8, 'item_name' => 'Lucky Me Noodles', 'item_price' => 15.00, 'item_stocks' => 100],
        ['item_id' => 9, 'item_name' => 'Corned Beef', 'item_price' => 65.00, 'item_stocks' => 40],
        ['item_id' => 10, 'item_name' => 'Spam', 'item_price' => 85.00, 'item_stocks' => 35],
        ['item_id' => 11, 'item_name' => 'Sardines', 'item_price' => 35.00, 'item_stocks' => 50],
        ['item_id' => 12, 'item_name' => 'Tuna', 'item_price' => 55.00, 'item_stocks' => 45],
        ['item_id' => 13, 'item_name' => 'Bread', 'item_price' => 45.00, 'item_stocks' => 30],
        ['item_id' => 14, 'item_name' => 'Pandesal', 'item_price' => 2.00, 'item_stocks' => 100],
        ['item_id' => 15, 'item_name' => 'Loaf Bread', 'item_price' => 60.00, 'item_stocks' => 25],
        ['item_id' => 16, 'item_name' => 'Chips', 'item_price' => 30.00, 'item_stocks' => 50],
        ['item_id' => 17, 'item_name' => 'Cookies', 'item_price' => 25.00, 'item_stocks' => 40],
        ['item_id' => 18, 'item_name' => 'Chocolate Bar', 'item_price' => 20.00, 'item_stocks' => 60]
    ];
}

// Get all receipts
$receipts_result = $conn->query("SELECT * FROM receipts ORDER BY receipt_id DESC");
$receipts_list = [];
while ($row = $receipts_result->fetch_assoc()) {
    $receipts_list[] = $row;
}

// Get all users
$users_result = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
$users_list = [];
while ($row = $users_result->fetch_assoc()) {
    $users_list[] = $row;
}

// Get today's sales
$today = date('Y-m-d');
$today_sales_result = $conn->query("SELECT SUM(total_price) as total FROM receipts WHERE receipt_date = '$today'");
$today_sales = 0;
if ($today_sales_result && $row = $today_sales_result->fetch_assoc()) {
    $today_sales = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - POS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f4f4f4; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 30px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 24pxnavbar .user-info; }
        . { display: flex; align-items: center; gap: 20px; }
        .navbar a { color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 5px; }
        .container { padding: 30px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .card .value { font-size: 32px; font-weight: bold; color: #333; }
        .card .peso { font-size: 20px; color: #667eea; }
        .section { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section h2 { margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; color: #333; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #667eea; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 5px; cursor: pointer; }
        .tab.active { background: #667eea; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .price { color: #28a745; font-weight: bold; }
        .stock-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; }
        .stock-high { background: #d4edda; color: #155724; }
        .stock-low { background: #fff3cd; color: #856404; }
        .stock-out { background: #f8d7da; color: #721c24; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 400px; max-width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; color: #333; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .action-buttons { display: flex; gap: 5px; align-items: center; }
        .stock-input { width: 70px !important; padding: 5px !important; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Admin)</span>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="cards">
            <div class="card">
                <h3>Today's Sales</h3>
                <div class="value"><span class="peso">₱</span><?php echo number_format($today_sales, 2); ?></div>
            </div>
            <div class="card">
                <h3>Total Items</h3>
                <div class="value"><?php echo count($items_list); ?></div>
            </div>
            <div class="card">
                <h3>Total Receipts</h3>
                <div class="value"><?php echo count($receipts_list); ?></div>
            </div>
            <div class="card">
                <h3>Total Users</h3>
                <div class="value"><?php echo count($users_list); ?></div>
            </div>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('items')">Items</button>
            <button class="tab" onclick="showTab('receipts')">Receipts</button>
            <button class="tab" onclick="showTab('users')">Users</button>
        </div>
        
        <div id="items" class="tab-content active">
            <div class="section">
                <h2>Manage Items</h2>
                <form method="POST" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                    <h3>Add New Item</h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div class="form-group">
                            <label>Item Name</label>
                            <input type="text" name="item_name" required>
                        </div>
                        <div class="form-group">
                            <label>Price (₱)</label>
                            <input type="number" name="item_price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Stocks</label>
                            <input type="number" name="item_stocks" required>
                        </div>
                    </div>
                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                </form>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Price</th>
                            <th>Stocks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_list as $item): ?>
                            <tr>
                                <td><?php echo $item['item_id']; ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td class="price">₱<?php echo number_format($item['item_price'], 2); ?></td>
                                <td>
                                    <?php if ($item['item_stocks'] > 10): ?>
                                        <span class="stock-badge stock-high"><?php echo $item['item_stocks']; ?></span>
                                    <?php elseif ($item['item_stocks'] > 0): ?>
                                        <span class="stock-badge stock-low"><?php echo $item['item_stocks']; ?></span>
                                    <?php else: ?>
                                        <span class="stock-badge stock-out">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="showEditModal(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['item_name']); ?>', <?php echo $item['item_price']; ?>)">Edit</button>
                                        <input type="number" class="stock-input" id="stock_<?php echo $item['item_id']; ?>" value="<?php echo $item['item_stocks']; ?>">
                                        <button type="button" class="btn btn-success btn-sm" onclick="updateStocks(<?php echo $item['item_id']; ?>)">Update</button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteItem(<?php echo $item['item_id']; ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="receipts" class="tab-content">
            <div class="section">
                <h2>Receipts / Sales History</h2>
                <?php if (empty($receipts_list)): ?>
                    <p>No receipts yet.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Receipt ID</th>
                            <th>Date</th>
                            <th>Customer Name</th>
                            <th>Items</th>
                            <th>Total Price</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts_list as $receipt): ?>
                            <tr>
                                <td>#<?php echo $receipt['receipt_id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($receipt['receipt_date'])); ?></td>
                                <td><?php echo htmlspecialchars($receipt['customer_name']); ?></td>
                                <td><?php 
                                    $items = json_decode($receipt['items_json'], true);
                                    if ($items) {
                                        foreach ($items as $item) {
                                            $qty = $item['quantity'] ?? $item['qty'] ?? 0;
                                            echo htmlspecialchars($item['name']) . " (x" . $qty . ")<br>";
                                        }
                                    }
                                ?></td>
                                <td class="price">₱<?php echo number_format($receipt['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($receipt['remarks'] ?? '-'); ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteReceipt(<?php echo $receipt['receipt_id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="users" class="tab-content">
            <div class="section">
                <h2>User Accounts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_list as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Item</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <input type="hidden" id="editItemId">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" id="editItemName">
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" id="editItemPrice" step="0.01">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: #ccc; color: #333;" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEditItem()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            var i, tabcontent, tabs;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tabs = document.getElementsByClassName("tab");
            for (i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            event.target.classList.add("active");
        }
        
        function showEditModal(itemId, itemName, itemPrice) {
            document.getElementById('editItemId').value = itemId;
            document.getElementById('editItemName').value = itemName;
            document.getElementById('editItemPrice').value = itemPrice;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Update stocks function
        function updateStocks(itemId) {
            var stockInput = document.getElementById('stock_' + itemId);
            var newStock = stockInput.value;
            
            if (newStock < 0) {
                alert('Stock cannot be negative');
                return;
            }
            
            var formData = new FormData();
            formData.append('update_stocks_ajax', '1');
            formData.append('item_id', itemId);
            formData.append('item_stocks', newStock);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating stocks');
            });
        }
        
        // Save edit item function
        function saveEditItem() {
            var itemId = document.getElementById('editItemId').value;
            var itemName = document.getElementById('editItemName').value;
            var itemPrice = document.getElementById('editItemPrice').value;
            
            if (!itemName || !itemPrice) {
                alert('Please fill in all fields');
                return;
            }
            
            var formData = new FormData();
            formData.append('update_item_ajax', '1');
            formData.append('item_id', itemId);
            formData.append('item_name', itemName);
            formData.append('item_price', itemPrice);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating item');
            });
        }
        
        // Delete item function
        function deleteItem(itemId) {
            if (!confirm('Are you sure you want to delete this item?')) {
                return;
            }
            
            var formData = new FormData();
            formData.append('delete_item_ajax', '1');
            formData.append('item_id', itemId);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting item');
            });
        }
        
// Delete receipt function
        function deleteReceipt(receiptId) {
            if (!confirm('Are you sure you want to delete this receipt?')) {
                return;
            }
            
            // Get the receipt total price before deleting
            var table = document.querySelector('#receipts table tbody');
            var receiptTotal = 0;
            var rows = table.querySelectorAll('tr');
            rows.forEach(function(row) {
                if (row.querySelector('td:first-child').textContent === '#' + receiptId) {
                    var priceText = row.querySelector('.price').textContent.replace('₱', '').replace(',', '');
                    receiptTotal = parseFloat(priceText) || 0;
                }
            });
            
            var formData = new FormData();
            formData.append('delete_receipt_ajax', '1');
            formData.append('receipt_id', receiptId);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Receipt deleted successfully!');
                    
                    // Remove the row from the table without reloading the page
                    var rows = table.querySelectorAll('tr');
                    rows.forEach(function(row) {
                        if (row.querySelector('td:first-child').textContent === '#' + receiptId) {
                            row.remove();
                        }
                    });
                    
                    // Update receipt count in cards without reloading
                    var receiptCount = document.querySelector('.card:nth-child(3) .value');
                    if (receiptCount) {
                        var currentCount = parseInt(receiptCount.textContent);
                        receiptCount.textContent = currentCount - 1;
                    }
                    
                    // Update Today's Sales - subtract the deleted receipt amount
                    var todaySalesEl = document.querySelector('.card:first-child .value');
                    if (todaySalesEl) {
                        var currentSales = parseFloat(todaySalesEl.textContent.replace(/₱|,/g, '')) || 0;
                        var newSales = currentSales - receiptTotal;
                        todaySalesEl.innerHTML = '<span class="peso">₱</span>' + newSales.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    
                    // Check if table is empty
                    if (table && table.children.length === 0) {
                        var receiptsSection = document.querySelector('#receipts .section');
                        receiptsSection.innerHTML = '<h2>Receipts / Sales History</h2><p>No receipts yet.</p>';
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting receipt');
            });
        }
    </script>
    
    <!-- Google Adsense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2862802439514164"
     crossorigin="anonymous"></script>
</body>
</html>
