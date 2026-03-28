<?php

// Detect base path dynamically (optional but helpful)
$base_url = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ? '' : '/php-ecomm';

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

function isProductAvailableForPurchase($product)
{
    if (!is_array($product)) {
        return false;
    }

    $has_stock = isset($product['stock_quantity']) && (int) $product['stock_quantity'] > 0;
    $is_in_stock = !empty($product['in_stock']);

    return $has_stock && $is_in_stock;
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
    $quantity = (int) $quantity;

    if ($quantity < 1) {
        return false;
    }

    // Get product information
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product || !isProductAvailableForPurchase($product)) {
        return false;
    }

    $current_quantity = isset($_SESSION['cart'][$product_id]) ? (int) $_SESSION['cart'][$product_id]['quantity'] : 0;
    $new_quantity = $current_quantity + $quantity;

    if ($new_quantity > (int) $product['stock_quantity']) {
        return false;
    }

    // Check if product already in cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
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

function renderCatalogSidebar($config = [])
{
    $title = isset($config['title']) ? $config['title'] : 'Filters';
    $form_action = isset($config['form_action']) ? $config['form_action'] : '';
    $method = isset($config['method']) ? $config['method'] : 'GET';
    $categories = isset($config['categories']) ? $config['categories'] : [];
    $current_category = isset($config['current_category']) ? $config['current_category'] : '';
    $current_min_price = isset($config['current_min_price']) ? $config['current_min_price'] : '';
    $current_max_price = isset($config['current_max_price']) ? $config['current_max_price'] : '';
    $current_sort = isset($config['current_sort']) ? $config['current_sort'] : '';
    $sort_options = isset($config['sort_options']) ? $config['sort_options'] : [];
    $hidden_fields = isset($config['hidden_fields']) ? $config['hidden_fields'] : [];
    $clear_url = isset($config['clear_url']) ? $config['clear_url'] : $form_action;
    $category_links_mode = !empty($config['category_links_mode']);
    $show_price = array_key_exists('show_price', $config) ? (bool) $config['show_price'] : true;
    $show_sort = array_key_exists('show_sort', $config) ? (bool) $config['show_sort'] : true;
    $submit_label = isset($config['submit_label']) ? $config['submit_label'] : 'Apply Filters';

    ob_start();
?>
    <aside class="catalog-sidebar">
        <div class="catalog-sidebar-card">
            <div class="catalog-sidebar-header">
                <h3 class="catalog-sidebar-title"><?php echo htmlspecialchars($title); ?></h3>
                <a href="<?php echo htmlspecialchars($clear_url); ?>" class="catalog-clear-link">Clear</a>
            </div>

            <?php if ($category_links_mode): ?>
                <div class="catalog-filter-group">
                    <span class="catalog-filter-label">Browse Categories</span>
                    <div class="catalog-category-links">
                        <a href="<?php echo htmlspecialchars($clear_url); ?>"
                            class="catalog-category-link <?php echo empty($current_category) ? 'active' : ''; ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo htmlspecialchars($form_action . '?category=' . urlencode($category['id']) . ($current_sort !== '' ? '&sort=' . urlencode($current_sort) : '')); ?>"
                                class="catalog-category-link <?php echo (string) $current_category === (string) $category['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($show_sort && !empty($sort_options)): ?>
                    <form action="<?php echo htmlspecialchars($form_action); ?>" method="<?php echo htmlspecialchars($method); ?>" class="catalog-filter-form">
                        <?php foreach ($hidden_fields as $name => $value): ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
                        <?php endforeach; ?>
                        <?php if ($current_category !== ''): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($current_category); ?>">
                        <?php endif; ?>

                        <div class="catalog-filter-group">
                            <label for="catalog-sort" class="catalog-filter-label">Sort By</label>
                            <select id="catalog-sort" name="sort" class="form-control catalog-select" onchange="this.form.submit()">
                                <?php foreach ($sort_options as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $current_sort === $value ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <form action="<?php echo htmlspecialchars($form_action); ?>" method="<?php echo htmlspecialchars($method); ?>" class="catalog-filter-form">
                    <?php foreach ($hidden_fields as $name => $value): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endforeach; ?>

                    <div class="catalog-filter-group">
                        <label for="catalog-category" class="catalog-filter-label">Category</label>
                        <select id="catalog-category" name="category" class="form-control catalog-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (string) $current_category === (string) $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($show_price): ?>
                        <div class="catalog-filter-group">
                            <span class="catalog-filter-label">Price Range</span>
                            <div class="catalog-price-range">
                                <div class="catalog-price-field">
                                    <label for="catalog-min-price">Min Price</label>
                                    <input type="number" id="catalog-min-price" name="min_price" class="form-control" placeholder="0" value="<?php echo htmlspecialchars($current_min_price); ?>">
                                </div>
                                <div class="catalog-price-field">
                                    <label for="catalog-max-price">Max Price</label>
                                    <input type="number" id="catalog-max-price" name="max_price" class="form-control" placeholder="5000" value="<?php echo htmlspecialchars($current_max_price); ?>">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_sort && !empty($sort_options)): ?>
                        <div class="catalog-filter-group">
                            <label for="catalog-sort" class="catalog-filter-label">Sort By</label>
                            <select id="catalog-sort" name="sort" class="form-control catalog-select">
                                <?php foreach ($sort_options as $value => $label): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $current_sort === $value ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="catalog-filter-actions">
                        <button type="submit" class="btn btn-primary btn-block"><?php echo htmlspecialchars($submit_label); ?></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </aside>
<?php

    return ob_get_clean();
}
