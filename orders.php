    <?php
    // ... (your existing code above)

    require_once 'includes/db_connect.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Function to get all orders for a user
    function getUserOrders($conn, $user_id)
    {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Function to get order items for an order
    function getOrderItems($conn, $order_id)
    {
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cancel order logic
    if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
        $order_id = intval($_GET['cancel']);
        // Only allow cancel if order belongs to user and is pending
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $update = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $update->execute([$order_id]);
            $msg = "Order cancelled successfully.";
        } else {
            $msg = "Order cannot be cancelled.";
        }
    }

    // Fetch orders
    $orders = getUserOrders($conn, $_SESSION['user_id']);
    ?>

    <!DOCTYPE html>
    <html>

    <head>
        <title>My Orders</title>
    </head>

    <body>
        <?php require_once 'includes/header.php'; ?>
        <div class="container my-5">
            <h2 class="mb-4">My Orders</h2>

            <?php if (isset($msg)) echo "<div class='alert alert-info'>$msg</div>"; ?>

            <?php if (empty($orders)): ?>
            <div class="alert alert-warning">No orders found.</div>
            <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <?php
                            $items = getOrderItems($conn, $order['id']);
                            foreach ($items as $item):
                            ?>
                    <div class="row border-bottom py-2">
                        <div class="col-md-3"><strong>Name:</strong><?= htmlspecialchars($item['name']) ?></div>
                        <div class="col-md-2"><strong>price:</strong> ₹<?= htmlspecialchars($item['price']) ?></div>
                        <div class="col-md-2"><strong>quantity:</strong><?= htmlspecialchars($item['quantity']) ?></div>
                        <div class="col-md-2"><strong>Discount:</strong> ₹<?= htmlspecialchars($order['discount']) ?>
                        </div>
                        <div class="col-md-2"> <strong>Total:</strong> ₹<?= htmlspecialchars($order['total']) ?>
                        </div>

                    </div>
                    <?php endforeach; ?>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Coupon:</strong> <?= htmlspecialchars($order['coupon_code']) ?: '-' ?></p>
                            <p><strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?: '-' ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
                            <p><strong>Tax:</strong> ₹<?= htmlspecialchars($order['tax']) ?></p>
                            <p><strong>Subtotal:</strong> ₹<?= htmlspecialchars($item['subtotal']) ?></p>
                            <p><strong>Payment Mode:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                            <p><strong>Ordered On:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                        </div>
                    </div>

                    <div class="text-end">
                        <?php if ($order['status'] == 'pending'): ?>
                        <a href="?cancel=<?= $order['id'] ?>" class="btn btn-danger btn-sm"
                            onclick="return confirm('Cancel this order?')">Cancel</a>
                        <?php elseif ($order['status'] == 'cancelled'): ?>
                        <span class="text-muted danger">Order Cancelled</span>
                        <?php else: ?>
                        <span class="text-muted">No Action</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php require_once 'includes/footer.php'; ?>
    </body>


    </html>