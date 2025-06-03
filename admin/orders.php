<?php
$page_title = "Manage Orders";
$is_admin = true;
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Handle AJAX requests
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if ($data && isset($data['action']) && $data['action'] === 'update_status') {
        $order_id = (int)$data['order_id'];
        $status = $data['status'];
        
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $result = $stmt->execute([$status, $order_id]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
            exit;
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query and parameters
$params = [];
$where_clauses = [];

if (!empty($status_filter)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "";

switch ($sort) {
    case 'oldest':
        $order_by = " ORDER BY o.created_at ASC";
        break;
    case 'total_high':
        $order_by = " ORDER BY o.total DESC";
        break;
    case 'total_low':
        $order_by = " ORDER BY o.total ASC";
        break;
    case 'newest':
    default:
        $order_by = " ORDER BY o.created_at DESC";
        break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total orders count for pagination
$count_sql = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id" . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Get orders
$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id" . 
        $where_sql . $order_by . 
        " LIMIT " . $per_page . " OFFSET " . $offset;
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Current URL without pagination for pagination links
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
$query_string = $_GET;
unset($query_string['page']);
$base_url = $current_url . '?' . http_build_query($query_string) . (empty($query_string) ? '' : '&');

require_once '../includes/header.php';
?>

<div class="admin-container">
    <!-- Admin Sidebar -->
    <?php  require_once './includes/sidebar.php'; ?>

    <!-- Admin Content -->
    <div class="admin-content" id="admin-content">
        <div class="admin-top-bar">
            <button class="admin-toggle-sidebar" id="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <div class="admin-user">
                <img src="<?php echo isset($_SESSION['photo']) ? htmlspecialchars($_SESSION['photo']) : '/assets/user-icon.svg'; ?>"
                    alt="User" class="admin-user-avatar">
                <div class="admin-user-info">
                    <div class="admin-user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="admin-user-role">Administrator</div>
                </div>
            </div>
        </div>

        <h1>Orders</h1>

        <!-- Filters -->
        <div class="filters-bar">
            <form action="/admin/orders.php" method="GET" class="row">
                <div class="filter-item">
                    <label for="status">Order Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>
                            Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped
                        </option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>
                            Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                            Cancelled</option>
                        <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded
                        </option>
                    </select>
                </div>

                <div class="filter-item">
                    <label>Date Range</label>
                    <div class="date-range">
                        <input type="date" name="date_from" class="form-control"
                            value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From">
                        <input type="date" name="date_to" class="form-control"
                            value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To">
                    </div>
                </div>

                <div class="filter-item">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="total_high" <?php echo $sort === 'total_high' ? 'selected' : ''; ?>>Total (High
                            to Low)</option>
                        <option value="total_low" <?php echo $sort === 'total_low' ? 'selected' : ''; ?>>Total (Low to
                            High)</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control"
                        placeholder="Order ID, customer name, email..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="/admin/orders.php" class="btn btn-outline">Clear</a>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <div class="data-table-title">Orders List</div>
                <div class="data-table-info">
                    Showing <?php echo min(($page - 1) * $per_page + 1, $total_orders); ?> to
                    <?php echo min($page * $per_page, $total_orders); ?> of <?php echo $total_orders; ?> orders
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Payment Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td>
                            <div class="customer-info">
                                <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?>
                                </div>
                                <div class="customer-email"><?php echo htmlspecialchars($order['customer_email']); ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <select class="form-control update-status" data-order-id="<?php echo $order['id']; ?>">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                    Pending</option>
                                <option value="processing"
                                    <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing
                                </option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>
                                    Shipped</option>
                                <option value="delivered"
                                    <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled"
                                    <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="refunded"
                                    <?php echo $order['status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </td>
                        <td><?php echo formatPrice($order['total']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="action-button view"
                                    title="View Order Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="javascript:void(0);" class="action-button print" title="Print Invoice"
                                    onclick="printInvoice(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No orders found matching your criteria.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="data-table-footer">
                <div class="data-table-pagination">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo $base_url; ?>page=1" class="page-button">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="page-button">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php
                    // Show 3 pages before and after current page
                    $start_page = max(1, $page - 3);
                    $end_page = min($total_pages, $page + 3);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>"
                        class="page-button <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="page-button">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="<?php echo $base_url; ?>page=<?php echo $total_pages; ?>" class="page-button">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="page-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="notification-container"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleBtn = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('admin-sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Update order status
    const statusSelects = document.querySelectorAll('.update-status');

    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.getAttribute('data-order-id');
            const newStatus = this.value;

            // Send AJAX request to update status
            fetch('/admin/orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        order_id: orderId,
                        status: newStatus
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Order status updated successfully', 'success');
                    } else {
                        showNotification('Error updating order status', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to update order status', 'error');
                });
        });
    });

    // Function to show notifications
    function showNotification(message, type) {
        const container = document.getElementById('notification-container');

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        container.appendChild(notification);

        // Auto-remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 3000);
    }

    // Function to print invoice (placeholder)
    window.printInvoice = function(orderId) {
        // In a real application, this would open a print-friendly page or generate a PDF
        window.open(`order-invoice.php?id=${orderId}`, '_blank');
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>