<?php
require_once '../bootstrap.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect(url('dashboard.php'));
}

Security::generateToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('forgot-password.php'));
    }

    $email = Security::sanitizeEmail($_POST['email'] ?? '');
    
    if (empty($email)) {
        setErrors(['email' => 'Email address is required.']);
    } elseif (!Security::validateEmail($email)) {
        setErrors(['email' => 'Please enter a valid email address.']);
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Find user by email
            $stmt = $db->prepare("SELECT id, email, first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate secure random token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Delete any existing unused tokens for this user
                $deleteStmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ? AND used = 0");
                $deleteStmt->execute([$user['id']]);
                
                // Insert new reset token
                $insertStmt = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $insertStmt->execute([$user['id'], $token, $expiresAt]);
                
                // Send reset email
                $resetLink = url('reset-password.php?token=' . $token);
                $notification = new Notification();
                $subject = "Password Reset Request - Andcorp Autos";
                $message = "Hello {$user['first_name']},\n\n";
                $message .= "We received a request to reset your password for your Andcorp Autos account.\n\n";
                $message .= "To reset your password, click the link below:\n";
                $message .= $resetLink . "\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you did not request a password reset, please ignore this email and your password will remain unchanged.\n\n";
                $message .= "For security reasons, never share this link with anyone.";
                
                $notification->create($user['id'], null, 'email', $subject, $message);
                
                error_log("Password reset token generated for user ID {$user['id']}, email: {$email}");
            } else {
                error_log("Password reset requested for non-existent email: {$email}");
            }
            
            // Always show success message (security: don't reveal if email exists)
            setSuccess('If an account exists with that email address, you will receive a password reset link shortly. Please check your email.');
            clearOld();
            redirect(url('forgot-password.php'));
            
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            setErrors(['general' => 'An error occurred. Please try again later.']);
        }
    }
}

$title = "Forgot Password";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/head.php'; ?>
    <title><?php echo htmlspecialchars($title); ?> - Andcorp Autos</title>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <img src="<?php echo url('assets/images/logo.png'); ?>" alt="Andcorp Autos" class="auth-logo">
                <h1 class="h3 mb-2">Forgot Password?</h1>
                <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <?php if ($successMsg = success()): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($generalError = error('general')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $generalError; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars(url('forgot-password.php')); ?>" method="POST">
                <?php echo Security::csrfField(); ?>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" 
                           class="form-control <?php echo error('email') ? 'is-invalid' : ''; ?>" 
                           id="email" 
                           name="email" 
                           value="<?php echo old('email'); ?>" 
                           placeholder="your.email@example.com"
                           required>
                    <?php if (error('email')): ?>
                        <div class="invalid-feedback"><?php echo error('email'); ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-envelope"></i> Send Reset Link
                </button>

                <div class="text-center">
                    <a href="<?php echo url('login.php'); ?>" class="text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to Login
                    </a>
                </div>
            </form>
        </div>

        <div class="text-center mt-4">
            <p class="text-muted small">
                Don't have an account? 
                <a href="<?php echo url('register.php'); ?>" class="text-decoration-none">Sign up</a>
            </p>
        </div>
    </div>

    <?php clearErrors(); clearOld(); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

