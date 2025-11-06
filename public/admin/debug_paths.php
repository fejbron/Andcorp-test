<?php
/**
 * Diagnostic script to check path detection on live server
 * This helps identify why 404 errors occur after session expiration
 * 
 * Access this file directly to see path information
 * Delete this file after debugging
 */

require_once '../bootstrap.php';

// Only allow access if logged in as admin (for security)
if (!Auth::check() || !Auth::isAdmin()) {
    die('Access denied. Admin login required.');
}

header('Content-Type: text/plain');

echo "=== PATH DETECTION DIAGNOSTICS ===\n\n";

echo "Server Variables:\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NOT SET') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "\n";

echo "Calculated Values:\n";
echo "getBasePath(): " . getBasePath() . "\n";
echo "url('login.php'): " . url('login.php') . "\n";
echo "url('dashboard.php'): " . url('dashboard.php') . "\n";
echo "url('admin/dashboard.php'): " . url('admin/dashboard.php') . "\n";
echo "\n";

echo "File System:\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "Real path of bootstrap.php: " . realpath(__DIR__ . '/../bootstrap.php') . "\n";
echo "\n";

echo "Session Info:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "User ID: " . (Auth::userId() ?? 'NOT LOGGED IN') . "\n";
echo "\n";

echo "Test Redirects:\n";
echo "To test redirects, you can modify this script to call redirect()\n";
echo "But be careful - it will actually redirect!\n";

