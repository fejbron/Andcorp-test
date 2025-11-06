<?php
/**
 * Debug script to check quote request IDs in database
 */
require_once '../../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/plain');

echo "=== Quote Request ID Debug Tool ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all quote requests
    $stmt = $db->query("SELECT id, request_number, status, created_at FROM quote_requests ORDER BY id DESC LIMIT 20");
    $quoteRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "1. All Quote Requests in Database:\n";
    echo "   Total found: " . count($quoteRequests) . "\n\n";
    
    if (empty($quoteRequests)) {
        echo "   ⚠️  No quote requests found in database!\n";
    } else {
        foreach ($quoteRequests as $qr) {
            echo "   - ID: " . $qr['id'] . " | Request #: " . ($qr['request_number'] ?? 'N/A') . " | Status: " . ($qr['status'] ?? 'N/A') . " | Created: " . ($qr['created_at'] ?? 'N/A') . "\n";
        }
    }
    
    echo "\n";
    
    // Check specific ID if provided
    $checkId = $_GET['id'] ?? null;
    if ($checkId) {
        echo "2. Checking Specific ID: " . htmlspecialchars($checkId) . "\n";
        
        $sanitizedId = Security::sanitizeInt($checkId);
        echo "   Raw ID: " . var_export($checkId, true) . "\n";
        echo "   Sanitized ID: " . var_export($sanitizedId, true) . "\n";
        
        if ($sanitizedId > 0) {
            $checkStmt = $db->prepare("SELECT * FROM quote_requests WHERE id = :id LIMIT 1");
            $checkStmt->bindValue(':id', $sanitizedId, PDO::PARAM_INT);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "   ✅ Found record:\n";
                echo "      Request Number: " . ($result['request_number'] ?? 'N/A') . "\n";
                echo "      Status: " . ($result['status'] ?? 'N/A') . "\n";
                echo "      Customer ID: " . ($result['customer_id'] ?? 'N/A') . "\n";
            } else {
                echo "   ❌ No record found with ID: " . $sanitizedId . "\n";
            }
        } else {
            echo "   ❌ Invalid ID after sanitization\n";
        }
    }
    
    echo "\n";
    echo "3. Test QuoteRequest Model:\n";
    $quoteRequestModel = new QuoteRequest();
    
    // Test with first ID if available
    if (!empty($quoteRequests)) {
        $testId = $quoteRequests[0]['id'];
        echo "   Testing findById() with ID: " . $testId . "\n";
        
        try {
            $result = $quoteRequestModel->findById($testId);
            if ($result) {
                echo "   ✅ findById() returned result\n";
                echo "      Request Number: " . ($result['request_number'] ?? 'N/A') . "\n";
            } else {
                echo "   ❌ findById() returned null/false\n";
            }
        } catch (Exception $e) {
            echo "   ❌ findById() threw exception: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== End Debug ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

