<?php
/**
 * Diagnostic script to debug quote request view page issues
 * Access: https://app.andcorpautos.com/public/admin/debug_quote_view.php?id=2
 */
require_once '../../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/plain');

$requestId = Security::sanitizeInt($_GET['id'] ?? 0);

echo "=== Quote Request View Debug Information ===\n\n";

echo "1. Request ID: " . ($requestId ?: 'NOT PROVIDED') . "\n\n";

if (!$requestId) {
    echo "ERROR: No request ID provided. Add ?id=2 to the URL.\n";
    exit;
}

echo "2. Authentication Status:\n";
echo "   User ID: " . (Auth::userId() ?? 'N/A') . "\n";
echo "   User Role: " . (Auth::userRole() ?? 'N/A') . "\n";
echo "   Is Staff: " . (Auth::isStaff() ? 'YES' : 'NO') . "\n";
echo "   Is Admin: " . (Auth::isAdmin() ? 'YES' : 'NO') . "\n\n";

echo "3. Database Connection:\n";
try {
    $db = Database::getInstance()->getConnection();
    $db->query("SELECT 1");
    echo "   Status: CONNECTED\n";
} catch (Exception $e) {
    echo "   Status: FAILED - " . $e->getMessage() . "\n";
    exit;
}
echo "\n";

echo "4. Quote Request Model:\n";
try {
    $quoteRequestModel = new QuoteRequest();
    echo "   Model: LOADED\n";
} catch (Exception $e) {
    echo "   Model: FAILED - " . $e->getMessage() . "\n";
    exit;
}
echo "\n";

echo "5. Finding Quote Request ID {$requestId}:\n";
try {
    $request = $quoteRequestModel->findById($requestId);
    
    if ($request === null || $request === false) {
        echo "   Result: NOT FOUND (returned null or false)\n";
        echo "   This would cause a redirect to the listing page.\n\n";
        
        // Check if the quote request exists at all
        echo "6. Checking if quote request exists in database:\n";
        $sql = "SELECT id, request_number, status, customer_id FROM quote_requests WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        $rawResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rawResult) {
            echo "   Found in database:\n";
            echo "   ID: " . $rawResult['id'] . "\n";
            echo "   Request Number: " . ($rawResult['request_number'] ?? 'N/A') . "\n";
            echo "   Status: " . ($rawResult['status'] ?? 'N/A') . "\n";
            echo "   Customer ID: " . ($rawResult['customer_id'] ?? 'N/A') . "\n";
            echo "\n";
            echo "   ISSUE: Quote request exists but findById() returned null.\n";
            echo "   This suggests a problem with the JOIN queries in findById().\n";
        } else {
            echo "   NOT FOUND in database.\n";
            echo "   The quote request with ID {$requestId} does not exist.\n";
        }
    } else {
        echo "   Result: FOUND\n";
        echo "   Request Number: " . ($request['request_number'] ?? 'N/A') . "\n";
        echo "   Status: " . ($request['status'] ?? 'N/A') . "\n";
        echo "   Customer ID: " . ($request['customer_id'] ?? 'N/A') . "\n";
        echo "   Customer User ID: " . ($request['customer_user_id'] ?? 'N/A') . "\n";
        echo "   Customer Name: " . ($request['customer_first_name'] ?? '') . ' ' . ($request['customer_last_name'] ?? '') . "\n";
        echo "\n";
        echo "   The quote request was found successfully.\n";
        echo "   If the view page is still redirecting, check for other errors.\n";
    }
} catch (PDOException $e) {
    echo "   ERROR: PDO Exception\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
    echo "   Error Info: " . print_r($e->errorInfo, true) . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    echo "   Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";
echo "7. URL Generation Test:\n";
echo "   url('admin/quote-requests.php'): " . url('admin/quote-requests.php') . "\n";
echo "   url('admin/quote-requests/view.php?id=' . \$requestId): " . url('admin/quote-requests/view.php?id=' . $requestId) . "\n";
echo "\n";

echo "8. Session Information:\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Redirect After Login: " . ($_SESSION['redirect_after_login'] ?? 'N/A') . "\n";
echo "\n";

echo "=== End Debug Information ===\n";
?>

