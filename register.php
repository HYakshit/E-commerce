<?php
// Check if this is an AJAX request
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax || strpos($content_type, 'application/json') !== false) {
    header('Content-Type: application/json');
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data && isset($data['action']) && $data['action'] === 'firebase_register') {
        try {
            // Your existing registration logic here
            // For example:
            $uid = $data['uid'];
            $email = $data['email'];
            $name = $data['name'];
            $photo = $data['photo'];
            
            // Add user to database
            // ... your database insertion code ...
            
            // Start session and set session variables
            session_start();
            $_SESSION['user_id'] = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $name;
            
            echo json_encode([
                'success' => true,
                'redirect' => '/index.php',
                'message' => 'Registration successful'
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // If we get here, invalid request
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

// If not an AJAX request, continue with regular HTML page
$page_title = "Register";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to home page
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

require_once 'includes/header.php';
?>

<section class="auth-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">Create an Account</h2>
                    <p class="auth-subtitle">Join us today to start shopping!</p>
                </div>
                
                <div id="error-message" class="alert alert-danger" style="display: none;"></div>
                
                <form id="register-form" class="auth-form" data-validate>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required data-min-length="6">
                        <small class="form-text">Password must be at least 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" class="form-control" required data-match="password">
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                            <label for="terms" class="form-check-label">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                </form>
                
                <div class="auth-separator">
                    <span>or</span>
                </div>
                
                <div class="social-login">
                    <button id="google-login-btn" class="social-btn">
                        <i class="fab fa-google"></i>
                        Continue with Google
                    </button>
                </div>
                
                <div class="auth-footer">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
