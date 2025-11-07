<?php
/**
 * Password Reset Debug Script
 * 
 * This script tests the password reset functionality and helps diagnose issues.
 * Use this to check if the system is configured correctly.
 */

require_once '../../bootstrap.php';
Auth::requireAdmin();

header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Password Reset System Diagnostic ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Check if password_resets table exists
    echo "1. Checking password_resets table...\n";
    $checkTable = $db->query("SHOW TABLES LIKE 'password_resets'");
    if ($checkTable->rowCount() > 0) {
        echo "   ✓ Table exists\n";
        
        // Show table structure
        $structure = $db->query("DESCRIBE password_resets");
        echo "   Columns: ";
        $cols = [];
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = $row['Field'];
        }
        echo implode(', ', $cols) . "\n";
    } else {
        echo "   ✗ Table NOT found - Run setup_password_resets.php\n";
    }
    echo "\n";
    
    // 2. Check if there are any tokens
    echo "2. Checking existing tokens...\n";
    try {
        $tokenStmt = $db->query("SELECT COUNT(*) as count, 
                                         SUM(CASE WHEN used = 0 AND expires_at > NOW() THEN 1 ELSE 0 END) as valid_count,
                                         SUM(CASE WHEN used = 1 THEN 1 ELSE 0 END) as used_count,
                                         SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_count
                                  FROM password_resets");
        $tokenStats = $tokenStmt->fetch(PDO::FETCH_ASSOC);
        echo "   Total tokens: " . $tokenStats['count'] . "\n";
        echo "   Valid (unused & not expired): " . $tokenStats['valid_count'] . "\n";
        echo "   Used: " . $tokenStats['used_count'] . "\n";
        echo "   Expired: " . $tokenStats['expired_count'] . "\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 3. Test token generation
    echo "3. Testing token generation...\n";
    try {
        $testToken = bin2hex(random_bytes(32));
        echo "   ✓ Generated token: " . substr($testToken, 0, 20) . "...\n";
        echo "   Token length: " . strlen($testToken) . " characters\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 4. Test Auth::logActivity method
    echo "4. Testing Auth::logActivity method...\n";
    try {
        $userId = Auth::userId();
        if ($userId) {
            // Check if method exists
            if (method_exists('Auth', 'logActivity')) {
                echo "   ✓ Auth::logActivity method exists\n";
                
                // Check activity_logs table
                $checkActivityTable = $db->query("SHOW TABLES LIKE 'activity_logs'");
                if ($checkActivityTable->rowCount() > 0) {
                    echo "   ✓ activity_logs table exists\n";
                } else {
                    echo "   ⚠ activity_logs table NOT found - logActivity calls may fail\n";
                }
            } else {
                echo "   ⚠ Auth::logActivity method NOT found\n";
            }
        }
    } catch (Exception $e) {
        echo "   ⚠ Error testing logActivity: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 5. Test email sending
    echo "5. Testing Notification system...\n";
    try {
        if (class_exists('Notification')) {
            echo "   ✓ Notification class exists\n";
            
            // Check notifications table
            $checkNotifTable = $db->query("SHOW TABLES LIKE 'notifications'");
            if ($checkNotifTable->rowCount() > 0) {
                echo "   ✓ notifications table exists\n";
            } else {
                echo "   ⚠ notifications table NOT found\n";
            }
        } else {
            echo "   ✗ Notification class NOT found\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // 6. Test URL generation
    echo "6. Testing URL generation...\n";
    $testUrl = url('reset-password.php?token=test123');
    echo "   Generated URL: " . $testUrl . "\n";
    echo "   HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
    echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    echo "\n";
    
    // 7. Check recent errors in logs
    echo "7. Recent password reset activity (last 5)...\n";
    try {
        $recentStmt = $db->query("SELECT pr.*, u.email 
                                  FROM password_resets pr
                                  LEFT JOIN users u ON pr.user_id = u.id
                                  ORDER BY pr.created_at DESC
                                  LIMIT 5");
        $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($recent)) {
            echo "   No password reset requests found.\n";
        } else {
            foreach ($recent as $item) {
                echo "   - User: " . $item['email'] . "\n";
                echo "     Token: " . substr($item['token'], 0, 20) . "...\n";
                echo "     Created: " . $item['created_at'] . "\n";
                echo "     Expires: " . $item['expires_at'] . "\n";
                echo "     Used: " . ($item['used'] ? 'Yes' : 'No') . "\n";
                echo "\n";
            }
        }
    } catch (Exception $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Diagnostic Complete ===\n\n";
    
    echo "If you're experiencing HTTP 500 errors:\n";
    echo "1. Check your server error logs (error_log file)\n";
    echo "2. Ensure the password_resets table exists\n";
    echo "3. Ensure the activity_logs table exists\n";
    echo "4. Check that Auth::logActivity() method is working\n";
    echo "5. Verify database credentials are correct\n";
    echo "6. Check PHP error reporting in php.ini\n";
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

