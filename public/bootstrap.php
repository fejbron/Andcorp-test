<?php
// Load Security and Headers classes first (before anything else)
require_once __DIR__ . '/../app/Security.php';
require_once __DIR__ . '/../app/Headers.php';
require_once __DIR__ . '/../app/Validator.php';
require_once __DIR__ . '/../app/Cache.php';

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    // Use 'Lax' for local development to allow POST requests with CSRF tokens
    // In production, consider using 'Strict' for better security
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 0); // Until browser closes
    
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Set security headers
Headers::setSecurityHeaders();

// Enable output compression
Headers::enableCompression();

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/' . $class . '.php',
        __DIR__ . '/../app/Models/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Helper functions
function getBasePath() {
    static $basePath = null;
    if ($basePath === null) {
        // Method 1: Try REQUEST_URI first (most reliable for cPanel/live servers)
        // This works better because it reflects the actual URL being accessed
        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // Remove query string
            $requestUri = strtok($requestUri, '?');
            
            // Find the 'public' directory in the path
            if (strpos($requestUri, '/public/') !== false) {
                // Extract everything up to and including 'public'
                $parts = explode('/public/', $requestUri);
                $basePath = $parts[0] . '/public';
            } elseif (strpos($requestUri, '/public') !== false && substr($requestUri, -7) === '/public') {
                // Handle case where URI ends with /public
                $basePath = $requestUri;
            }
        }
        
        // Method 2: Try SCRIPT_NAME (works well on most servers)
        if (empty($basePath)) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
            
            if (!empty($scriptName)) {
                // Find the 'public' directory in the path
                if (strpos($scriptName, '/public/') !== false) {
                    // Extract everything up to and including 'public'
                    $parts = explode('/public/', $scriptName);
                    $basePath = $parts[0] . '/public';
                } elseif (strpos($scriptName, '/public') !== false && substr($scriptName, -7) === '/public') {
                    // Handle case where script name ends with /public
                    $basePath = $scriptName;
                } else {
                    // Fallback to script directory
                    $scriptDir = dirname($scriptName);
                    if ($scriptDir !== '/' && $scriptDir !== '\\' && $scriptDir !== '.') {
                        $basePath = rtrim($scriptDir, '/');
                    }
                }
            }
        }
        
        // Method 3: Use DOCUMENT_ROOT and SCRIPT_FILENAME (cPanel fallback)
        if (empty($basePath) && !empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
            $scriptFile = $_SERVER['SCRIPT_FILENAME'];
            
            if (strpos($scriptFile, $docRoot) === 0) {
                // Get relative path from document root
                $relativePath = str_replace($docRoot, '', $scriptFile);
                $relativePath = dirname($relativePath);
                
                // If it contains 'public', extract up to public
                if (strpos($relativePath, '/public') !== false) {
                    $parts = explode('/public', $relativePath);
                    $basePath = $parts[0] . '/public';
                } elseif ($relativePath !== '/' && $relativePath !== '\\' && $relativePath !== '.') {
                    $basePath = rtrim($relativePath, '/');
                }
            }
        }
        
        // Method 4: Use __DIR__ to calculate from current file location
        // __DIR__ in bootstrap.php is the public directory
        if (empty($basePath)) {
            $publicDir = __DIR__;
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
                if (strpos($publicDir, $docRoot) === 0) {
                    // Calculate relative path from document root
                    $relativePath = str_replace($docRoot, '', $publicDir);
                    $basePath = $relativePath ?: '/public';
                } else {
                    // If we can't calculate relative, assume /public
                    $basePath = '/public';
                }
            } else {
                // Fallback: assume /public
                $basePath = '/public';
            }
        }
        
        // Ensure basePath is properly formatted (no trailing slash unless it's root)
        if ($basePath !== '' && $basePath !== '/') {
            $basePath = rtrim($basePath, '/');
        }
    }
    return $basePath;
}

function redirect($url) {
    // If url() already returned an absolute URL, use it directly
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header("Location: $url");
        exit;
    }
    
    // Handle relative URLs (starting with /)
    if (strpos($url, '/') === 0) {
        $basePath = getBasePath();
        
        // Only add base path if it's not already in the URL
        if ($basePath !== '/' && $basePath !== '' && strpos($url, $basePath) !== 0) {
            $url = $basePath . $url;
        }
    } else {
        // Relative path without leading slash - prepend base path
        $basePath = getBasePath();
        if ($basePath !== '' && $basePath !== '/') {
            $url = $basePath . '/' . ltrim($url, '/');
        } else {
            $url = '/' . ltrim($url, '/');
        }
    }
    
    // Use absolute URL if possible (better for cPanel and prevents 404s)
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $absoluteUrl = $protocol . $_SERVER['HTTP_HOST'] . $url;
        header("Location: $absoluteUrl");
    } else {
        header("Location: $url");
    }
    exit;
}

function old($key, $default = '') {
    return $_SESSION['old'][$key] ?? $default;
}

function clearOld() {
    unset($_SESSION['old']);
}

function setOld($data) {
    $_SESSION['old'] = $data;
}

function error($key) {
    return $_SESSION['errors'][$key] ?? null;
}

function hasError($key) {
    return isset($_SESSION['errors'][$key]);
}

function setErrors($errors) {
    $_SESSION['errors'] = $errors;
}

function clearErrors() {
    unset($_SESSION['errors']);
}

function success() {
    $message = $_SESSION['success'] ?? null;
    unset($_SESSION['success']);
    return $message;
}

function setSuccess($message) {
    $_SESSION['success'] = $message;
}

function formatCurrency($amount, $currency = 'GHS') {
    // Handle null values for PHP 8+ compatibility
    $amount = $amount ?? 0;
    return $currency . ' ' . number_format((float)$amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function getStatusBadgeClass($status) {
    // Normalize status for comparison (case-insensitive)
    $statusLower = strtolower($status);
    
    $classes = [
        'pending' => 'warning',
        'purchased' => 'info',
        'delivered to port of load' => 'primary',
        'origin customs clearance' => 'secondary',
        'shipping' => 'primary',
        'arrived in ghana' => 'info',
        'ghana customs clearance' => 'secondary',
        'inspection' => 'info',
        'repair' => 'warning',
        'ready' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $classes[$statusLower] ?? 'secondary';
}

function getStatusLabel($status) {
    // Return the status as-is since it's already properly formatted in the database
    return $status;
}

function uploadFile($file, $directory = 'uploads/') {
    // Validate file upload
    $validation = Security::validateFileUpload($file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    $uploadDir = __DIR__ . '/../storage/' . $directory;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate secure filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Set proper file permissions
        chmod($filepath, 0644);
        return ['success' => true, 'path' => $directory . $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

function asset($path) {
    return '/' . ltrim($path, '/');
}

function url($path = '') {
    // Always use absolute paths starting from document root
    $basePath = getBasePath();
    
    // Handle query string separately to preserve it
    $queryString = '';
    if (strpos($path, '?') !== false) {
        $parts = explode('?', $path, 2);
        $path = $parts[0];
        $queryString = '?' . $parts[1];
    }
    
    // If basePath is empty or root, just use the path directly
    if ($basePath === '' || $basePath === '/') {
        if ($path === '' || $path === '/') {
            $fullPath = '/';
        } else {
            $fullPath = '/' . ltrim($path, '/');
        }
    } else {
        // Handle empty path or root
        if ($path === '' || $path === '/') {
            $fullPath = $basePath . '/';
        } else {
            // Remove leading slash if present
            $path = ltrim($path, '/');
            
            // Ensure basePath doesn't have trailing slash (we'll add it)
            $basePath = rtrim($basePath, '/');
            
            $fullPath = $basePath . '/' . $path;
        }
    }
    
    // Add query string back
    $fullPath .= $queryString;
    
    // Always return absolute URL if we have HTTP_HOST (essential for cPanel)
    if (!empty($_SERVER['HTTP_HOST'])) {
        // Check if already an absolute URL
        if (strpos($fullPath, 'http://') === 0 || strpos($fullPath, 'https://') === 0) {
            return $fullPath;
        }
        
        // Determine protocol
        $protocol = 'https://';
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            // Check for proxy headers (common on cPanel)
            if (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https') {
                $protocol = 'http://';
            }
        }
        
        return $protocol . $_SERVER['HTTP_HOST'] . $fullPath;
    }
    
    // Fallback: return relative path
    return $fullPath;
}
