<?php
$page_title = "Login";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to home page
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Handle AJAX login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // if email and password are set, handle login
    if (isset($_POST['email'], $_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
        // exit;
        // $user = getUserByEmail($email, $conn); // Your custom function

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            // Redirect to home
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }

    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

    if (strpos($content_type, 'application/json') !== false) {
        // Get the JSON data from the request body
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        // echo "<pre>";
        // print_r($data);
        // echo "</pre>";
        // exit;

        if ($data && isset($data['action']) && $data['action'] === 'firebase_login') {
            // Store user data from Firebase
            $uid = $data['uid'];
            $email = $data['email'];
            $name = $data['name'];
            $photo = $data['photo'] ?? null;

            // Store user in DB and session
            $user_id = storeFirebaseUser($uid, $email, $name, $conn);

            // Determine redirect URL
            $redirect = '/index.php';
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'redirect' => $redirect
            ]);
            exit;
        }
    }

    // Handle logout
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        // Clear user session
        session_unset();
        session_destroy();
        session_start();

        // Return success response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true
        ]);
        exit;
    }

    // Check if this is a login session check
    if (isset($_POST['action']) && $_POST['action'] === 'check_session') {
        $logged_in = isLoggedIn() && $_SESSION['firebase_uid'] === $_POST['uid'];

        header('Content-Type: application/json');
        echo json_encode([
            'logged_in' => $logged_in
        ]);
        exit;
    }
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

<?php require_once 'includes/footer.php'; ?>