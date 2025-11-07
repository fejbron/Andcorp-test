<?php
/**
 * Setup Password Resets Table
 * 
 * This script creates the password_resets table required for the forgot password feature.
 * Run this once after deployment.
 */

require_once '../../bootstrap.php';
Auth::requireAdmin(); // Only admins can run this

header('Content-Type: text/plain');

echo "=== Password Resets Table Setup ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'password_resets'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ Table 'password_resets' already exists.\n\n";
        
        // Show table structure
        echo "Current table structure:\n";
        $structure = $db->query("DESCRIBE password_resets");
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
        
        echo "\nIf you need to recreate the table, drop it first:\n";
        echo "DROP TABLE IF EXISTS password_resets;\n\n";
    } else {
        echo "Creating 'password_resets' table...\n\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires_at (expires_at),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        
        echo "✓ Table 'password_resets' created successfully!\n\n";
        
        echo "Table structure:\n";
        $structure = $db->query("DESCRIBE password_resets");
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    }
    
    echo "\n=== Setup Complete ===\n";
    echo "\nForgot Password feature is now available at:\n";
    echo "  - " . url('forgot-password.php') . "\n";
    echo "  - Link is also added to the login page\n\n";
    
    echo "Features:\n";
    echo "  ✓ Secure password reset via email\n";
    echo "  ✓ Tokens expire after 1 hour\n";
    echo "  ✓ Tokens can only be used once\n";
    echo "  ✓ Email notifications with reset links\n\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database connection and permissions.\n";
}
?>

