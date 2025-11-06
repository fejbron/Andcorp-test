<?php
/**
 * Diagnostic script to test URL generation on live server
 * This helps identify path detection issues
 */
require_once '../bootstrap.php';
Auth::requireStaff();

header('Content-Type: text/plain');

echo "=== URL Generation Diagnostic Tool ===\n\n";

echo "1. Server Environment:\n";
echo "   HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "   SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "   SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "   DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "   HTTPS: " . ($_SERVER['HTTPS'] ?? 'off') . "\n";
echo "   HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'N/A') . "\n";
echo "\n";

echo "2. Base Path Detection:\n";
$basePath = getBasePath();
echo "   Detected Base Path: " . ($basePath ?: '(empty)') . "\n";
echo "\n";

echo "3. URL Generation Tests:\n";
$testPaths = [
    'admin/quote-requests.php',
    'admin/quote-requests/view.php?id=5',
    'admin/quote-requests/view.php?id=1',
    'admin/quote-requests/convert.php?id=5',
];

foreach ($testPaths as $path) {
    $generated = url($path);
    echo "   Input:  {$path}\n";
    echo "   Output: {$generated}\n";
    echo "\n";
}

echo "4. Current Page URL:\n";
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . 
              ($_SERVER['HTTP_HOST'] ?? '') . 
              ($_SERVER['REQUEST_URI'] ?? '');
echo "   Current URL: {$currentUrl}\n";
echo "\n";

echo "5. Test Link (ID=5):\n";
$testLink = url('admin/quote-requests/view.php?id=5');
echo "   Generated URL: {$testLink}\n";
echo "   Full HTML link: <a href=\"{$testLink}\">View Quote Request #5</a>\n";
echo "\n";

echo "=== End Diagnostic ===\n";
?>
