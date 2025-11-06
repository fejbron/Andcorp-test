<?php
/**
 * Diagnostic script to test file upload functionality
 * Access: https://app.andcorpautos.com/public/admin/test_upload.php
 * 
 * This script checks:
 * - Upload directory existence and permissions
 * - PHP file upload settings
 * - Fileinfo extension availability
 * - Database connection
 */

require_once '../bootstrap.php';
Auth::requireStaff();

$checks = [];
$errors = [];

// Check upload directories
$uploadDirs = [
    'cars' => __DIR__ . '/../uploads/cars',
    'documents' => __DIR__ . '/../uploads/documents'
];

foreach ($uploadDirs as $type => $dir) {
    $checks[$type] = [
        'exists' => is_dir($dir),
        'writable' => is_writable($dir),
        'path' => $dir
    ];
    
    if (!is_dir($dir)) {
        $errors[] = "Directory '$type' does not exist: $dir";
        // Try to create it
        if (@mkdir($dir, 0755, true)) {
            $checks[$type]['created'] = true;
            $checks[$type]['exists'] = true;
            $checks[$type]['writable'] = is_writable($dir);
        } else {
            $checks[$type]['created'] = false;
            $errors[] = "Failed to create directory '$type': $dir";
        }
    }
}

// Check PHP upload settings
$phpSettings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'System default'
];

// Check fileinfo extension
$fileinfoAvailable = function_exists('finfo_open');
if ($fileinfoAvailable) {
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $fileinfoWorking = ($finfo !== false);
    if ($finfo) {
        @finfo_close($finfo);
    }
} else {
    $fileinfoWorking = false;
}

// Check database connection
try {
    $db = Database::getInstance()->getConnection();
    $dbWorking = true;
    $dbError = null;
    
    // Check if order_documents table exists
    $stmt = $db->query("SHOW TABLES LIKE 'order_documents'");
    $tableExists = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $dbWorking = false;
    $dbError = $e->getMessage();
    $tableExists = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Diagnostic - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1>File Upload Diagnostic</h1>
        <p class="text-muted">This page checks if all requirements for file uploads are met.</p>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5>Errors Found:</h5>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Upload Directories</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($checks as $type => $check): ?>
                            <div class="mb-3">
                                <strong><?php echo ucfirst($type); ?>:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($check['path']); ?></small><br>
                                <span class="badge <?php echo $check['exists'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $check['exists'] ? 'Exists' : 'Missing'; ?>
                                </span>
                                <?php if ($check['exists']): ?>
                                    <span class="badge <?php echo $check['writable'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $check['writable'] ? 'Writable' : 'Not Writable'; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (isset($check['created']) && $check['created']): ?>
                                    <span class="badge bg-info">Created</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>PHP Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($phpSettings as $key => $value): ?>
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars($key); ?>:</strong>
                                <span class="text-muted"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Extensions</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Fileinfo Extension:</strong>
                            <span class="badge <?php echo $fileinfoAvailable ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $fileinfoAvailable ? 'Available' : 'Not Available'; ?>
                            </span>
                            <?php if ($fileinfoAvailable): ?>
                                <span class="badge <?php echo $fileinfoWorking ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $fileinfoWorking ? 'Working' : 'Not Working'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Database</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Connection:</strong>
                            <span class="badge <?php echo $dbWorking ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $dbWorking ? 'Connected' : 'Failed'; ?>
                            </span>
                        </div>
                        <?php if ($dbWorking): ?>
                            <div class="mb-2">
                                <strong>order_documents table:</strong>
                                <span class="badge <?php echo $tableExists ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $tableExists ? 'Exists' : 'Missing'; ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo htmlspecialchars($dbError); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <h5>Summary</h5>
            <p>
                <?php
                $allGood = true;
                foreach ($checks as $check) {
                    if (!$check['exists'] || !$check['writable']) {
                        $allGood = false;
                        break;
                    }
                }
                if (!$fileinfoAvailable) {
                    $allGood = false;
                }
                if (!$dbWorking || !$tableExists) {
                    $allGood = false;
                }
                
                if ($allGood) {
                    echo '<span class="badge bg-success">All checks passed! Uploads should work.</span>';
                } else {
                    echo '<span class="badge bg-warning">Some issues found. Please fix the errors above.</span>';
                }
                ?>
            </p>
        </div>
        
        <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-primary">Back to Dashboard</a>
    </div>
</body>
</html>

