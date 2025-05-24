<?php
$page_title = "Shopping Cart";
$page_script = "/js/cart.js";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Initialize cart if not exists
initCart();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX requests
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($content_type, 'application/json') !== false) {
        // Get the JSON data from the request body
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        
        if ($data && isset($data['action'])) {
            $response = ['success' => false];
            
            switch ($data['action']) {
                case 'add_to_cart':
                    if (isset($data['product_id']) && isset($data['quantity'])) {
                        $success = addToCart($data['product_id'], $conn, $data['quantity']);
                        $response = [
                            'success' => $success,
                            'cart_count' => getCartItemCount(),
                            'message' => $success ? 'Product added to cart' : 'Failed to add product to cart'
                        ];
                    }
                    break;
                
                case 'update_quantity':
                    if (isset($data['product_id']) && isset($data['quantity'])) {
                        $success = updateCartQuantity($data['product_id'], $data['quantity']);
                        $response = [
                            'success' => $success,
                            'cart_count' => getCartItemCount(),
                            'cart_total' => calculateCartTotal(),
                            'message' => $success ? 'Cart updated' : 'Failed to update cart'
                        ];
                    }
                    break;
                
                case 'remove_item':
                    if (isset($data['product_id'])) {
                        $success = removeFromCart($data['product_id']);
                        $response = [
                            'success' => $success,
                            'cart_count' => getCartItemCount(),
                            'cart_total' => calculateCartTotal(),
                            'message' => $success ? 'Item removed from cart' : 'Failed to remove item'
                        ];
                    }
                    break;
                
                case 'apply_coupon':
                    if (isset($data['coupon_code'])) {
                        // Simple coupon implementation (would be expanded in a real app)
                        $coupon_code = strtoupper(trim($data['coupon_code']));
                        
                        // Check if coupon exists - handle different database boolean values
                        $active_condition = dbBool(true);
                        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND active = $active_condition AND expiry_date >= CURDATE()");
                        $stmt->execute([$coupon_code]);
                        $coupon = $stmt->fetch();
                        
                        if ($coupon) {
                            // Store coupon in session
                            $_SESSION['coupon'] = [
                                'code' => $coupon['code'],
                                'type' => $coupon['type'],
                                'value' => $coupon['value']
                            ];
                            
                            // Calculate discount
                            $cart_total = calculateCartTotal();
                            $discount = 0;
                            
                            if ($coupon['type'] === 'percentage') {
                                $discount = $cart_total * ($coupon['value'] / 100);
                            } else {
                                $discount = min($coupon['value'], $cart_total);
                            }
                            
                            $response = [
                                'success' => true,
                                'message' => 'Coupon applied successfully',
                                'discount' => $discount,
                                'cart_total' => $cart_total - $discount
                            ];
                        } else {
                            $response = [
                                'success' => false,
                                'message' => 'Invalid or expired coupon code'
                            ];
                        }
                    }
                    break;
            }
            
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } else {
        // Handle regular form submissions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_to_cart':
                    if (isset($_POST['product_id'])) {
                        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                        addToCart($_POST['product_id'], $conn, $quantity);
                    }
                    break;
                
                case 'update_cart':
                    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
                        foreach ($_POST['quantity'] as $product_id => $quantity) {
                            updateCartQuantity($product_id, intval($quantity));
                        }
                    }
                    break;
                
                case 'clear_cart':
                    clearCart();
                    break;
            }
        }
        
        // Redirect back to cart to avoid form resubmission
        header('Location: cart.php');
        exit;
    }
}

require_once 'includes/header.php';
?>

<section class="cart-page">
    <div class="container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
        </div>
        <?php else: ?>
        
        <div class="row">
            <div class="col-12 col-md-8">
                <div class="cart-items">
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="action" value="update_cart">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                <tr class="cart-item">
                                    <td data-label="Product">
                                        <div class="cart-product">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                            <div>
                                                <a href="product-details.php?id=<?php echo $product_id; ?>" class="cart-item-name">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Price" class="item-price" data-price="<?php echo $item['price']; ?>">
                                        <?php echo formatPrice($item['price']); ?>
                                    </td>
                                    <td data-label="Quantity">
                                        <div class="quantity-input">
                                            <button type="button" class="quantity-btn">-</button>
                                            <input type="number" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="item-quantity" data-product-id="<?php echo $product_id; ?>">
                                            <button type="button" class="quantity-btn">+</button>
                                        </div>
                                    </td>
                                    <td data-label="Total" class="item-total">
                                        <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </td>
                                    <td data-label="Action">
                                        <button type="button" class="btn btn-danger btn-sm remove-item" data-product-id="<?php echo $product_id; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="cart-actions">
                            <button type="submit" class="btn btn-outline">Update Cart</button>
                            <a href="products.php" class="btn btn-outline">Continue Shopping</a>
                            <button type="button" id="clear-cart-btn" class="btn btn-danger">Clear Cart</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-items">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value"><?php echo formatPrice(calculateCartTotal()); ?></span>
                        </div>
                        
                        <?php 
                        // Display discount if coupon is applied
                        $discount = 0;
                        if (isset($_SESSION['coupon'])) {
                            $cart_total = calculateCartTotal();
                            if ($_SESSION['coupon']['type'] === 'percentage') {
                                $discount = $cart_total * ($_SESSION['coupon']['value'] / 100);
                            } else {
                                $discount = min($_SESSION['coupon']['value'], $cart_total);
                            }
                        ?>
                        <div class="summary-row">
                            <span class="summary-label">Discount (<?php echo htmlspecialchars($_SESSION['coupon']['code']); ?>)</span>
                            <span class="summary-value" id="discount-amount">-<?php echo formatPrice($discount); ?></span>
                        </div>
                        <?php } ?>
                        
                        <div class="summary-row">
                            <span class="summary-label">Shipping</span>
                            <span class="summary-value">Calculated at checkout</span>
                        </div>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value" id="cart-total"><?php echo formatPrice(calculateCartTotal() - $discount); ?></span>
                    </div>
                    
                    <?php if (!isset($_SESSION['coupon'])): ?>
                    <div class="coupon-form">
                        <form id="coupon-form" action="cart.php" method="POST">
                            <div class="form-group">
                                <label for="coupon_code">Apply Coupon</label>
                                <div class="input-group">
                                    <input type="text" id="coupon_code" name="coupon_code" class="form-control" placeholder="Enter coupon code">
                                    <button type="submit" class="btn btn-outline">Apply</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <a href="checkout.php" class="btn btn-primary btn-block checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div id="message-container"></div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clearCartBtn = document.getElementById('clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'cart.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'clear_cart';
                
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
