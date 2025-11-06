<?php
// Quick test of URL generation
$_SERVER['HTTP_HOST'] = 'app.andcorpautos.com';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/Andcorp-test/public/admin/quote-requests.php';
$_SERVER['SCRIPT_NAME'] = '/Andcorp-test/public/admin/quote-requests.php';
$_SERVER['DOCUMENT_ROOT'] = '/home/username/public_html';
$_SERVER['SCRIPT_FILENAME'] = '/home/username/public_html/Andcorp-test/public/admin/quote-requests.php';

require_once 'public/bootstrap.php';

echo "Base Path: " . getBasePath() . "\n";
echo "URL Test: " . url('admin/quote-requests/view.php?id=5') . "\n";
?>
