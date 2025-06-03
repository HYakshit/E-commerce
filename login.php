<?php
$page_title = "Login";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Handle AJAX and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Handle JSON requests (AJAX)
    if ($is_ajax || strpos($content_type, 'application/json') !== false) {
        header('Content-Type: application/json');
        
        // Get the JSON data
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        
        if ($data && isset($data['action'])) {
            switch ($data['action']) {
                case 'email_login':
                    // Validate required fields
                    if (!isset($data['email']) || !isset($data['password'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                        exit;
                    }

                    // Get user from database
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$data['email']]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($data['password'], $user['password'])) {
                        // Start session if not already started
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }

                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['is_admin'] = (bool)$user['is_admin'];

                        // Determine redirect URL
                        $redirect = '/index.php';
                        if ($_SESSION['is_admin'] && isset($_SESSION['admin_redirect'])) {
                            $redirect = $_SESSION['admin_redirect'];
                            unset($_SESSION['admin_redirect']);
                        }

                        echo json_encode([
                            'success' => true,
                            'redirect' => $redirect,
                            'is_admin' => $_SESSION['is_admin']
                        ]);
                        exit;
                    } else {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                        exit;
                    }
                    break;

                case 'logout':
                    // Clear all session data
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    // Clear session
                    session_unset();
                    session_destroy();
                    
                    // Set cache control headers
                    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                    header('Cache-Control: post-check=0, pre-check=0', false);
                    header('Pragma: no-cache');
                    
                    // Return success response
                    echo json_encode(['success' => true]);
                    exit;
                    
                case 'firebase_login':
                    // Store user data from Firebase
                    $uid = $data['uid'];
                    $email = $data['email'];
                    $name = $data['name'];
                    $photo = $data['photo'] ?? null;

                    // Start session if not already started
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Check if user exists in database first
                    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $existing_user = $stmt->fetch();

                    if ($existing_user) {
                        // Update Firebase UID if needed
                        if (empty($existing_user['firebase_uid'])) {
                            $stmt = $conn->prepare("UPDATE users SET firebase_uid = ? WHERE id = ?");
                            $stmt->execute([$uid, $existing_user['id']]);
                        }
                        
                        // Use existing user data
                        $_SESSION['user_id'] = $existing_user['id'];
                        $_SESSION['email'] = $existing_user['email'];
                        $_SESSION['name'] = $existing_user['name'];
                        $_SESSION['firebase_uid'] = $uid;
                        $_SESSION['is_admin'] = (bool)$existing_user['is_admin'];
                        
                        // Determine redirect URL based on user type and stored redirect
                        if ($_SESSION['is_admin']) {
                            $redirect = isset($_SESSION['admin_redirect']) ? $_SESSION['admin_redirect'] : '/admin/index.php';
                            unset($_SESSION['admin_redirect']);
                        } else {
                            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/index.php';
                            unset($_SESSION['redirect_after_login']);
                        }
                    } else {
                        // Create new user
                        $stmt = $conn->prepare("INSERT INTO users (firebase_uid, email, name, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$uid, $email, $name]);
                        
                        $_SESSION['user_id'] = $conn->lastInsertId();
                        $_SESSION['email'] = $email;
                        $_SESSION['name'] = $name;
                        $_SESSION['firebase_uid'] = $uid;
                        $_SESSION['is_admin'] = false;
                        $redirect = '/index.php';
                    }

                    echo json_encode([
                        'success' => true,
                        'redirect' => $redirect,
                        'is_admin' => $_SESSION['is_admin']
                    ]);
                    exit;
                    
                case 'check_session':
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $logged_in = isset($_SESSION['firebase_uid']) && $_SESSION['firebase_uid'] === $data['uid'];
                    
                    echo json_encode([
                        'logged_in' => $logged_in
                    ]);
                    exit;
            }
        }
        
        // Invalid JSON request
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    // Handle regular form POST login
    if (isset($_POST['email'], $_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}

// Only include header and show HTML for non-AJAX requests
if (!isset($is_ajax) || !$is_ajax) {
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
                    <h2 class="auth-title">Log In to Your Account</h2>
                    <p class="auth-subtitle">Welcome back! Please enter your details.</p>
                </div>

                <div id="error-message" class="alert alert-danger" style="display: none;"></div>

                <form id="login-form" class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required
                            data-min-length="6">
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
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
                    Don't have an account? <a href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
    require_once 'includes/footer.php';
}
?>