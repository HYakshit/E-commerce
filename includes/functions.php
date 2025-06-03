<?php
// Start the session at the beginning before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin()
{
    return isset($_SESSION['is_admin']) && ($_SESSION['is_admin'] === TRUE ||
        $_SESSION['is_admin'] === 't' || $_SESSION['is_admin'] === 1 ||
        $_SESSION['is_admin'] === '1' || $_SESSION['is_admin'] === true);
}

// Get current user ID
function getCurrentUserId()
{
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Format price with currency symbol
function formatPrice($price)
{
    return '₹' . number_format($price, 2);
}
// Sanitize input
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get cart item count
function getCartItemCount()
{
    if (!isset($_SESSION['cart'])) {
        return 0;
    }

    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }

    return $count;
}

// Initialize cart if not exists
function initCart()
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Add item to cart
function addToCart($product_id, $conn, $quantity = 1)
{
    initCart();

    // Get product information
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        return false;
    }

    // Check if product already in cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image' => $product['image']
        ];
    }

    return true;
}

// Update cart item quantity
function updateCartQuantity($product_id, $quantity)
{
    if (!isset($_SESSION['cart'][$product_id])) {
        return false;
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }

    return true;
}

// Remove item from cart
function removeFromCart($product_id)
{
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

// Calculate cart total
function calculateCartTotal()
{
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }

    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    return $total;
}

// Clear cart
function clearCart()
{
    $_SESSION['cart'] = [];
}

// Store Firebase user in session
function storeFirebaseUser($uid, $email, $name, $conn)
{
    global $db_type;

    // Check if user exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE firebase_uid = ? OR email = ?");
    $stmt->execute([$uid, $email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Create new user - handle date format differences between MySQL and PostgreSQL
        if ($db_type == 'postgresql') {
            $stmt = $conn->prepare("INSERT INTO users (firebase_uid, email, name, created_at) VALUES (?, ?, ?, NOW())");
        } else {
            // MySQL uses NOW() without timezone information
            $stmt = $conn->prepare("INSERT INTO users (firebase_uid, email, name, created_at) VALUES (?, ?, ?, NOW())");
        }
        $stmt->execute([$uid, $email, $name]);

        // Get the new user ID
        $user_id = $conn->lastInsertId();
        $is_admin = false; // Default new user is not admin
    } else {
        $user_id = $user['id'];
        $is_admin = (bool)$user['is_admin']; // Convert to boolean
        
        // Update existing user's Firebase UID if it's not set
        if (empty($user['firebase_uid'])) {
            $stmt = $conn->prepare("UPDATE users SET firebase_uid = ? WHERE id = ?");
            $stmt->execute([$uid, $user_id]);
        }
    }

    // Store user info in session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['firebase_uid'] = $uid;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;
    $_SESSION['is_admin'] = $is_admin;

    return $user_id;
}

// Generate pagination links
function generatePaginationLinks($current_page, $total_pages, $base_url)
{
    $links = '';

    // Previous page link
    if ($current_page > 1) {
        $links .= '<a href="' . $base_url . 'page=' . ($current_page - 1) . '" class="page-link">&laquo; Previous</a>';
    }

    // Page number links
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        if ($i == $current_page) {
            $links .= '<a href="#" class="page-link active">' . $i . '</a>';
        } else {
            $links .= '<a href="' . $base_url . 'page=' . $i . '" class="page-link">' . $i . '</a>';
        }
    }

    // Next page link
    if ($current_page < $total_pages) {
        $links .= '<a href="' . $base_url . 'page=' . ($current_page + 1) . '" class="page-link">Next &raquo;</a>';
    }

    return $links;
}