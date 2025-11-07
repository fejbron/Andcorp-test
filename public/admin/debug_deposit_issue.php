<?php
/**
 * Debug script to check why deposit isn't showing
 * Access: https://app.andcorpautos.com/public/admin/debug_deposit_issue.php?order_number=ORD-2025-0003
 */
require_once '../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/html; charset=utf-8');

$orderNumber = $_GET['order_number'] ?? '';
if (!$orderNumber) {
    die('Please provide order_number in URL: ?order_number=ORD-2025-0003');
}

$db = Database::getInstance()->getConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deposit Debug - <?php echo htmlspecialchars($orderNumber); ?></title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .error { border-left-color: #dc3545; }
        .success { border-left-color: #28a745; }
        .warning { border-left-color: #ffc107; }
        h2 { margin-top: 0; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Deposit Debug: <?php echo htmlspecialchars($orderNumber); ?></h1>
    
    <?php
    // Step 1: Find the order
    echo '<div class="section">';
    echo '<h2>1. Finding Order</h2>';
    try {
        $orderStmt = $db->prepare("SELECT * FROM orders WHERE order_number = :order_number LIMIT 1");
        $orderStmt->execute([':order_number' => $orderNumber]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            echo '<p class="success">✅ Order found!</p>';
            echo '<table>';
            echo '<tr><th>ID</th><td>' . $order['id'] . '</td></tr>';
            echo '<tr><th>Order Number</th><td>' . htmlspecialchars($order['order_number']) . '</td></tr>';
            echo '<tr><th>Customer ID</th><td>' . $order['customer_id'] . '</td></tr>';
            echo '<tr><th>Total Cost</th><td>GHS ' . number_format($order['total_cost'], 2) . '</td></tr>';
            echo '<tr><th>Total Deposits (DB)</th><td>GHS ' . number_format($order['total_deposits'] ?? 0, 2) . '</td></tr>';
            echo '<tr><th>Balance Due (DB)</th><td>GHS ' . number_format($order['balance_due'], 2) . '</td></tr>';
            echo '<tr><th>Status</th><td>' . htmlspecialchars($order['status']) . '</td></tr>';
            echo '</table>';
            
            $orderId = $order['id'];
        } else {
            echo '<p class="error">❌ Order not found with number: ' . htmlspecialchars($orderNumber) . '</p>';
            echo '</div></body></html>';
            exit;
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div></body></html>';
        exit;
    }
    echo '</div>';
    
    // Step 2: Query deposits directly from database
    echo '<div class="section">';
    echo '<h2>2. Deposits in Database (Direct Query)</h2>';
    try {
        $depositStmt = $db->prepare("SELECT * FROM deposits WHERE order_id = :order_id ORDER BY transaction_date DESC, transaction_time DESC");
        $depositStmt->execute([':order_id' => $orderId]);
        $deposits = $depositStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($deposits)) {
            echo '<p class="warning">⚠️ No deposits found in database for order_id: ' . $orderId . '</p>';
        } else {
            echo '<p class="success">✅ Found ' . count($deposits) . ' deposit(s)</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Amount</th><th>Currency</th><th>Method</th><th>Bank</th><th>Reference</th><th>Status</th><th>Date/Time</th></tr>';
            
            $totalVerified = 0;
            foreach ($deposits as $dep) {
                $isVerified = $dep['status'] === 'verified';
                if ($isVerified) {
                    $totalVerified += floatval($dep['amount']);
                }
                
                echo '<tr' . ($isVerified ? ' style="background: #d4edda;"' : '') . '>';
                echo '<td>' . $dep['id'] . '</td>';
                echo '<td><strong>' . $dep['currency'] . ' ' . number_format($dep['amount'], 2) . '</strong></td>';
                echo '<td>' . htmlspecialchars($dep['currency']) . '</td>';
                echo '<td>' . htmlspecialchars($dep['payment_method']) . '</td>';
                echo '<td>' . htmlspecialchars($dep['bank_name'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($dep['reference_number'] ?? 'N/A') . '</td>';
                echo '<td><strong>' . htmlspecialchars($dep['status']) . '</strong></td>';
                echo '<td>' . $dep['transaction_date'] . ' ' . $dep['transaction_time'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            echo '<p><strong>Total Verified Deposits:</strong> GHS ' . number_format($totalVerified, 2) . '</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error querying deposits: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Step 3: Use Deposit Model
    echo '<div class="section">';
    echo '<h2>3. Using Deposit Model</h2>';
    try {
        $depositModel = new Deposit();
        $modelDeposits = $depositModel->getByOrder($orderId);
        
        if (empty($modelDeposits)) {
            echo '<p class="warning">⚠️ Deposit model returned no results</p>';
        } else {
            echo '<p class="success">✅ Deposit model returned ' . count($modelDeposits) . ' deposit(s)</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error using Deposit model: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Step 4: Check if there are deposits with different customer_id
    echo '<div class="section">';
    echo '<h2>4. Checking for Mismatched Customer IDs</h2>';
    try {
        $mismatchStmt = $db->prepare("
            SELECT d.*, d.customer_id as deposit_customer_id, o.customer_id as order_customer_id
            FROM deposits d
            LEFT JOIN orders o ON d.order_id = o.id
            WHERE d.order_id = :order_id AND d.customer_id != o.customer_id
        ");
        $mismatchStmt->execute([':order_id' => $orderId]);
        $mismatches = $mismatchStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($mismatches)) {
            echo '<p class="success">✅ No customer ID mismatches found</p>';
        } else {
            echo '<p class="warning">⚠️ Found ' . count($mismatches) . ' deposit(s) with mismatched customer IDs:</p>';
            echo '<pre>' . print_r($mismatches, true) . '</pre>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Step 5: Recent deposits (last 10)
    echo '<div class="section">';
    echo '<h2>5. Recent Deposits (All Orders, Last 10)</h2>';
    try {
        $recentStmt = $db->query("
            SELECT d.*, o.order_number 
            FROM deposits d 
            LEFT JOIN orders o ON d.order_id = o.id 
            ORDER BY d.id DESC 
            LIMIT 10
        ");
        $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recent)) {
            echo '<p class="warning">⚠️ No deposits in entire system</p>';
        } else {
            echo '<table>';
            echo '<tr><th>ID</th><th>Order #</th><th>Order ID</th><th>Amount</th><th>Status</th><th>Date</th></tr>';
            foreach ($recent as $r) {
                $highlight = ($r['order_id'] == $orderId) ? ' style="background: #fff3cd;"' : '';
                echo '<tr' . $highlight . '>';
                echo '<td>' . $r['id'] . '</td>';
                echo '<td>' . htmlspecialchars($r['order_number'] ?? 'N/A') . '</td>';
                echo '<td>' . $r['order_id'] . '</td>';
                echo '<td>' . $r['currency'] . ' ' . number_format($r['amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($r['status']) . '</td>';
                echo '<td>' . $r['transaction_date'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Step 6: Action links
    echo '<div class="section">';
    echo '<h2>6. Action Links</h2>';
    echo '<p><a href="' . url('admin/orders/edit.php?id=' . $orderId) . '">View Order Details Page</a></p>';
    echo '<p><a href="' . url('admin/deposits.php?search=' . urlencode($orderNumber)) . '">Search Deposits for this Order</a></p>';
    echo '<p><a href="' . url('admin/deposits/add.php?order_id=' . $orderId) . '">Add New Deposit</a></p>';
    echo '</div>';
    ?>
    
</body>
</html>

