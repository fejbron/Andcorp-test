<?php
/**
 * Database Configuration
 * 
 * This file supports both environment variables and direct configuration.
 * 
 * For Namecheap shared hosting:
 * - Update the values below directly (recommended)
 * - Or set environment variables in cPanel (Advanced)
 * 
 * IMPORTANT: Never commit database.php with real credentials to version control
 */

return [
    // Database host (usually 'localhost' on Namecheap shared hosting)
    'host' => getenv('DB_HOST') ?: 'localhost',
    
    // Database port (usually 3306)
    'port' => getenv('DB_PORT') ?: '3306',
    
    // Database name (e.g., 'cpses_username_andcorp' on Namecheap)
    // Format: cpses_CPANELUSERNAME_dbname
    'database' => getenv('DB_DATABASE') ?: 'your_database_name',
    
    // Database username (e.g., 'cpses_username_dbuser' on Namecheap)
    // Format: cpses_CPANELUSERNAME_dbuser
    'username' => getenv('DB_USERNAME') ?: 'your_database_user',
    
    // Database password
    'password' => getenv('DB_PASSWORD') ?: 'your_database_password',
    
    // Character set
    'charset' => 'utf8mb4',
    
    // Collation
    'collation' => 'utf8mb4_unicode_ci',
];

