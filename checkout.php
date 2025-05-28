<?php
$page_title = "Checkout";
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Save current page as redirect after login
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Get user information
$user_id = getCurrentUserId();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's previous addresses if any
$stmt = $conn->prepare("SELECT * FROM saved_addresses WHERE user_id = ? ");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();
// print_r($addresses);
// exit;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form
    $errors = [];

    // Basic validation
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'country', 'payment_method'];
    $data = [
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['address2'] ?? '',
        $_POST['city'],
        $_POST['state'],
        $_POST['zip_code'],
        $_POST['country'],
        isset($_POST['same_address']) ? 1 : 0
    ];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    // If validation passes, process the order
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->beginTransaction();

            // Calculate totals
            $subtotal = calculateCartTotal();
            $discount = 0;

            // Apply coupon discount if any
            if (isset($_SESSION['coupon'])) {
                if ($_SESSION['coupon']['type'] === 'percentage') {
                    $discount = $subtotal * ($_SESSION['coupon']['value'] / 100);
                } else {
                    $discount = min($_SESSION['coupon']['value'], $subtotal);
                }
            }

            // Calculate shipping, tax, etc.
            $shipping = 0; // You might calculate based on address, weight, etc.
            $tax = $subtotal * 0.1; // Example: 10% tax rate
            $total = $subtotal - $discount + $shipping + $tax;

            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, discount, shipping, tax, total, status, payment_method, coupon_code, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
            $stmt->execute([
                $user_id,
                $subtotal,
                $discount,
                $shipping,
                $tax,
                $total,
                $_POST['payment_method'],
                isset($_SESSION['coupon']) ? $_SESSION['coupon']['code'] : null
            ]);

            $order_id = $conn->lastInsertId();
            if (!empty($_POST[$save_address])) {    // save address
                $stmt = $conn->prepare("INSERT INTO save_addresses (user_id,order_id, first_name, last_name, email, phone, address, address2, city, state, zip_code, country, is_shipping, is_billing) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([
                    $user_id,
                    $order_id,
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['address2'] ?? '',
                    $_POST['city'],
                    $_POST['state'],
                    $_POST['zip_code'],
                    $_POST['country'],
                    isset($_POST['same_address']) ? 1 : 0
                ]);
            }


            // Save shipping address
            $stmt = $conn->prepare("INSERT INTO order_addresses (order_id, first_name, last_name, email, phone, address, address2, city, state, zip_code, country, is_shipping, is_billing) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute([
                $order_id,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_POST['address2'] ?? '',
                $_POST['city'],
                $_POST['state'],
                $_POST['zip_code'],
                $_POST['country'],
                isset($_POST['same_address']) ? 1 : 0
            ]);


            // If billing address is different from shipping
            if (!isset($_POST['same_address'])) {
                $stmt = $conn->prepare("INSERT INTO order_addresses (order_id, first_name, last_name, email, phone, address, address2, city, state, zip_code, country, is_shipping, is_billing) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)");
                $stmt->execute([
                    $order_id,
                    $_POST['billing_first_name'],
                    $_POST['billing_last_name'],
                    $_POST['billing_email'],
                    $_POST['billing_phone'],
                    $_POST['billing_address'],
                    $_POST['billing_address2'] ?? '',
                    $_POST['billing_city'],
                    $_POST['billing_state'],
                    $_POST['billing_zip_code'],
                    $_POST['billing_country']
                ]);
            }

            foreach ($_SESSION['cart'] as $product_id => $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, name, price, quantity, subtotal) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $order_id,
                    $product_id,
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $item['price'] * $item['quantity']
                ]);

                // Update product stock
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $product_id]);
            }

            // Commit transaction
            $conn->commit();

            // Clear cart and coupon
            clearCart();
            unset($_SESSION['coupon']);

            // Redirect to thank you page
            $_SESSION['order_id'] = $order_id;
            header('Location: order-success.php');
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $errors[] = "An error occurred while processing your order. Please try again.";
        }
    }
}

require_once 'includes/header.php';
?>

<section class="checkout-page">
    <div class="container">
        <h1>Checkout</h1>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="checkout.php" method="POST" data-validate>
            <div class="checkout-grid">
                <!-- Shipping Information -->
                <div class="checkout-form">
                    <div class="checkout-section">
                        <h3 class="checkout-section-title">Shipping Information</h3>

                        <?php if (!empty($addresses)):
                            //     echo "
                            // <pre>";
                            //     print_r($addresses);
                            //     exit;
                        ?>


                        <dipv class="saved-addresses">
                            <label>Use a saved address:</label>
                            <select id="saved-address" class="form-control">
                                <option value="">Enter new address</option>
                                <?php foreach ($addresses as $address): ?>
                                <option value="<?php echo $address['phone']; ?>">
                                    <?php echo ($address['address'] . ', ' . $address['city'] . ', ' . $address['state']); ?>

                                </option>
                                <?php endforeach; ?>
                            </select>
                        </dipv>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" required
                                        value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" required
                                        value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-control" required
                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" required
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address *</label>
                            <input type="text" id="address" name="address" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="address2">Address Line 2</label>
                            <input type="text" id="address2" name="address2" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="state">State/Province *</label>
                                    <input type="text" id="state" name="state" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="zip_code">ZIP/Postal Code *</label>
                                    <input type="text" id="zip_code" name="zip_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="country">Country *</label>
                                    <select id="country" name="country" class="form-control" required>
                                        <option value="">Select Country</option>
                                        <option value="IN">India</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="AU">Australia</option>
                                        <!-- Add more countries as needed -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="save_address" name="save_address" class="form-check-input">
                                <label for="save_address" class="form-check-label">Save this address for future
                                    orders</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="same_address" name="same_address" class="form-check-input"
                                    checked>
                                <label for="same_address" class="form-check-label">Billing address same as
                                    shipping</label>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address (initially hidden) -->
                    <div id="billing-address-section" class="checkout-section" style="display: none;">
                        <h3 class="checkout-section-title">Billing Information</h3>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_first_name">First Name *</label>
                                    <input type="text" id="billing_first_name" name="billing_first_name"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_last_name">Last Name *</label>
                                    <input type="text" id="billing_last_name" name="billing_last_name"
                                        class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_email">Email Address *</label>
                                    <input type="email" id="billing_email" name="billing_email" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_phone">Phone Number *</label>
                                    <input type="tel" id="billing_phone" name="billing_phone" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="billing_address">Address *</label>
                            <input type="text" id="billing_address" name="billing_address" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="billing_address2">Address Line 2</label>
                            <input type="text" id="billing_address2" name="billing_address2" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_city">City *</label>
                                    <input type="text" id="billing_city" name="billing_city" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_state">State/Province *</label>
                                    <input type="text" id="billing_state" name="billing_state" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_zip_code">ZIP/Postal Code *</label>
                                    <input type="text" id="billing_zip_code" name="billing_zip_code"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="billing_country">Country *</label>
                                    <select id="billing_country" name="billing_country" class="form-control">
                                        <option value="">Select Country</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="AU">Australia</option>
                                        <!-- Add more countries as needed -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-section">
                        <h3 class="checkout-section-title">Payment Method</h3>

                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="payment_credit_card" name="payment_method" value="credit_card"
                                    checked>
                                <label for="payment_credit_card">
                                    <span>Credit Card</span>
                                </label>
                                <img src="https://via.placeholder.com/120x30" alt="Credit Cards">
                            </div>

                            <div class="payment-method">
                                <input type="radio" id="payment_paypal" name="payment_method" value="paypal">
                                <label for="payment_paypal">
                                    <span>PayPal</span>
                                </label>
                                <img src="https://via.placeholder.com/80x30" alt="PayPal">
                            </div>

                            <div class="payment-method">
                                <input type="radio" id="payment_bank_transfer" name="payment_method"
                                    value="bank_transfer">
                                <label for="payment_bank_transfer">
                                    <span>Bank Transfer</span>
                                </label>
                            </div>
                        </div>

                        <!-- Credit Card Form (shown/hidden based on selected payment method) -->
                        <div id="credit-card-form">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="card_number">Card Number</label>
                                        <input type="text" id="card_number" class="form-control"
                                            placeholder="1234 5678 9012 3456">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="expiry_date">Expiry Date</label>
                                        <input type="text" id="expiry_date" class="form-control" placeholder="MM/YY">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="cvv">CVV</label>
                                        <input type="text" id="cvv" class="form-control" placeholder="123">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="name_on_card">Name on Card</label>
                                        <input type="text" id="name_on_card" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="checkout-section">
                        <h3 class="checkout-section-title">Order Summary</h3>

                        <div class="order-items">
                            <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-image">
                                <div class="order-item-details">
                                    <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="order-item-price"><?php echo formatPrice($item['price']); ?></div>
                                    <div class="order-item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="order-item-total">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-summary-totals">
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
                                <span class="summary-label">Discount
                                    (<?php echo htmlspecialchars($_SESSION['coupon']['code']); ?>)</span>
                                <span class="summary-value">-<?php echo formatPrice($discount); ?></span>
                            </div>
                            <?php } ?>

                            <div class="summary-row">
                                <span class="summary-label">Shipping</span>
                                <span class="summary-value">Free</span>
                            </div>

                            <div class="summary-row">
                                <span class="summary-label">Tax (10%)</span>
                                <span
                                    class="summary-value"><?php echo formatPrice(calculateCartTotal() * 0.1); ?></span>
                            </div>

                            <div class="summary-row summary-total">
                                <span class="summary-label">Total</span>
                                <span class="summary-value">
                                    <?php
                                    $total = calculateCartTotal() - $discount + (calculateCartTotal() * 0.1);
                                    echo formatPrice($total);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <div class="form-group">
                            <label for="order_notes">Order Notes</label>
                            <textarea id="order_notes" name="order_notes" class="form-control" rows="3"
                                placeholder="Special instructions for delivery or order details"></textarea>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                                <label for="terms" class="form-check-label">I agree to the <a href="terms.php"
                                        target="_blank">Terms and Conditions</a></label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle billing address section
    const sameAddressCheckbox = document.getElementById('same_address');
    const billingAddressSection = document.getElementById('billing-address-section');

    sameAddressCheckbox.addEventListener('change', function() {
        billingAddressSection.style.display = this.checked ? 'none' : 'block';

        // Toggle required attribute on billing fields
        const billingInputs = billingAddressSection.querySelectorAll('input, select');
        billingInputs.forEach(input => {
            input.required = !this.checked;
        });
    });

    // Payment method toggle
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const creditCardForm = document.getElementById('credit-card-form');

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'credit_card') {
                creditCardForm.style.display = 'block';
            } else {
                creditCardForm.style.display = 'none';
            }
        });
    });

    // Fill form from saved address
    const savedAddressSelect = document.getElementById('saved-address');
    if (savedAddressSelect) {
        savedAddressSelect.addEventListener('change', function() {
            const addressId = this.value;

            if (addressId) {

                // Here you'd normally fetch the address data via AJAX
                // For this example, we'll use dummy data
                const addresses = <?php echo json_encode($addresses); ?>;
                // console.log(selectedAddress);
                const selectedAddress = addresses.find(addr => addr.phone == addressId);

                if (selectedAddress) {
                    console.log(selectedAddress);
                    document.getElementById('first_name').value = selectedAddress.first_name ||
                        '';
                    document.getElementById('last_name').value = selectedAddress.last_name ||
                        '';
                    document.getElementById('address').value = selectedAddress.address || '';
                    document.getElementById('address2').value = selectedAddress.address2 || '';
                    document.getElementById('city').value = selectedAddress.city || '';
                    document.getElementById('state').value = selectedAddress.state || '';
                    document.getElementById('zip_code').value = selectedAddress.zip_code || '';
                    document.getElementById('country').value = selectedAddress.country || '';
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>