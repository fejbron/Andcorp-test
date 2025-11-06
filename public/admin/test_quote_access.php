<?php
/**
 * Simple diagnostic to test quote request access
 * Access: https://app.andcorpautos.com/public/admin/test_quote_access.php?id=2
 */
require_once '../../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quote Request Access Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        h2 { margin-top: 0; border-bottom: 2px solid #333; padding-bottom: 10px; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Quote Request Access Test</h1>
    
    <div class="section">
        <h2>1. URL Parameters</h2>
        <p><strong>GET Parameters:</strong></p>
        <pre><?php print_r($_GET); ?></pre>
        
        <?php
        $rawId = $_GET['id'] ?? null;
        $sanitizedId = Security::sanitizeInt($rawId);
        ?>
        
        <p><strong>Raw ID:</strong> <?php echo var_export($rawId, true); ?></p>
        <p><strong>Sanitized ID:</strong> <?php echo var_export($sanitizedId, true); ?></p>
        
        <?php if (!$sanitizedId): ?>
            <p class="error">❌ No valid ID provided. Add ?id=2 to the URL.</p>
        <?php else: ?>
            <p class="success">✅ Valid ID received</p>
        <?php endif; ?>
    </div>
    
    <?php if ($sanitizedId): ?>
        
        <div class="section">
            <h2>2. Database Connection</h2>
            <?php
            try {
                $db = Database::getInstance()->getConnection();
                $db->query("SELECT 1");
                echo '<p class="success">✅ Database connected successfully</p>';
            } catch (Exception $e) {
                echo '<p class="error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
                exit;
            }
            ?>
        </div>
        
        <div class="section">
            <h2>3. Check if Quote Request Exists (Simple Query)</h2>
            <?php
            try {
                $checkSql = "SELECT id, request_number, status, customer_id FROM quote_requests WHERE id = :id LIMIT 1";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->bindValue(':id', $sanitizedId, PDO::PARAM_INT);
                $checkStmt->execute();
                $basicRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($basicRecord) {
                    echo '<p class="success">✅ Quote request exists in database</p>';
                    echo '<pre>' . print_r($basicRecord, true) . '</pre>';
                } else {
                    echo '<p class="error">❌ Quote request ID ' . $sanitizedId . ' not found in database</p>';
                    
                    // Show what IDs exist
                    $allStmt = $db->query("SELECT id, request_number FROM quote_requests ORDER BY id DESC LIMIT 10");
                    $allRecords = $allStmt->fetchAll(PDO::FETCH_ASSOC);
                    echo '<p><strong>Available quote request IDs:</strong></p>';
                    echo '<pre>' . print_r($allRecords, true) . '</pre>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ Error checking database: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <?php if (isset($basicRecord) && $basicRecord): ?>
            
            <div class="section">
                <h2>4. Check Customer Data</h2>
                <?php
                try {
                    $customerCheckSql = "SELECT c.*, u.first_name, u.last_name, u.email 
                                        FROM customers c 
                                        LEFT JOIN users u ON c.user_id = u.id 
                                        WHERE c.id = :customer_id";
                    $customerStmt = $db->prepare($customerCheckSql);
                    $customerStmt->bindValue(':customer_id', $basicRecord['customer_id'], PDO::PARAM_INT);
                    $customerStmt->execute();
                    $customerData = $customerStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($customerData) {
                        echo '<p class="success">✅ Customer data found</p>';
                        echo '<pre>' . print_r($customerData, true) . '</pre>';
                    } else {
                        echo '<p class="error">⚠️ Customer data not found for customer_id: ' . $basicRecord['customer_id'] . '</p>';
                        echo '<p class="info">This might cause issues with the JOIN query in findById()</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="error">❌ Error checking customer: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
            
            <div class="section">
                <h2>5. Test QuoteRequest::findById() Method</h2>
                <?php
                try {
                    $quoteRequestModel = new QuoteRequest();
                    $result = $quoteRequestModel->findById($sanitizedId);
                    
                    if ($result) {
                        echo '<p class="success">✅ findById() returned data</p>';
                        echo '<p><strong>Request Number:</strong> ' . htmlspecialchars($result['request_number'] ?? 'N/A') . '</p>';
                        echo '<p><strong>Status:</strong> ' . htmlspecialchars($result['status'] ?? 'N/A') . '</p>';
                        echo '<p><strong>Customer Name:</strong> ' . htmlspecialchars(($result['customer_first_name'] ?? '') . ' ' . ($result['customer_last_name'] ?? '')) . '</p>';
                        echo '<details><summary>View Full Data</summary><pre>' . print_r($result, true) . '</pre></details>';
                    } else {
                        echo '<p class="error">❌ findById() returned null/false</p>';
                        echo '<p class="info">Check error logs for details from findById() method</p>';
                    }
                } catch (PDOException $e) {
                    echo '<p class="error">❌ PDO Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<pre>SQL State: ' . htmlspecialchars($e->getCode()) . '</pre>';
                    echo '<pre>Error Info: ' . print_r($e->errorInfo, true) . '</pre>';
                } catch (Exception $e) {
                    echo '<p class="error">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                }
                ?>
            </div>
            
            <div class="section">
                <h2>6. Action Links</h2>
                <?php if (isset($result) && $result): ?>
                    <a href="<?php echo url('admin/quote-requests/view.php?id=' . $sanitizedId); ?>" class="btn">
                        ➡️ Try View Page Now
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo url('admin/quote-requests.php'); ?>" class="btn" style="background: #6c757d;">
                    📋 Back to Quote Requests List
                </a>
            </div>
            
        <?php endif; ?>
        
    <?php else: ?>
        
        <div class="section">
            <h2>Available Quote Requests</h2>
            <?php
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT id, request_number, status, created_at FROM quote_requests ORDER BY id DESC LIMIT 20");
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($requests) {
                    echo '<p>Click an ID to test it:</p>';
                    echo '<ul>';
                    foreach ($requests as $req) {
                        echo '<li>';
                        echo '<a href="?id=' . $req['id'] . '" class="btn" style="background: #28a745;">Test ID ' . $req['id'] . '</a> ';
                        echo 'Request #' . htmlspecialchars($req['request_number']) . ' - ';
                        echo htmlspecialchars($req['status']) . ' - ';
                        echo date('M j, Y', strtotime($req['created_at']));
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="error">No quote requests found in database.</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
    <?php endif; ?>
    
    <div class="section">
        <h2>📝 Notes</h2>
        <ul>
            <li>This test bypasses the normal view page logic</li>
            <li>It shows exactly what data is available at each step</li>
            <li>Check the error logs for detailed messages from findById()</li>
            <li>If findById() returns null but the record exists, there's an issue with the JOIN queries</li>
        </ul>
    </div>
    
</body>
</html>

