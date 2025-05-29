<?php
$page_title = "Admin Dashboard";
$is_admin = true;
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is admin
// if (!isLoggedIn() || !isAdmin()) {
//     header('Location: /admin/login.php');
//     exit();
// }

// Get dashboard statistics
$today = date('Y-m-d');
$last_30_days = date('Y-m-d', strtotime('-30 days'));

// Total orders
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders");
$stmt->execute();
$total_orders = $stmt->fetchColumn();

// Total revenue
$stmt = $conn->prepare("SELECT SUM(total) FROM orders");
$stmt->execute();
$total_revenue = $stmt->fetchColumn();

// Total customers
$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$total_customers = $stmt->fetchColumn();

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) FROM products");
$stmt->execute();
$total_products = $stmt->fetchColumn();

// Recent orders
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Low stock products
$stmt = $conn->prepare("SELECT * FROM products WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC LIMIT 5");
$stmt->execute();
$low_stock_products = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="admin-container">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-logo">
            <img src="/assets/logo.svg" alt="ShopNow Admin">
            <span>ShopNow Admin</span>
        </div>

        <div class="admin-menu">
            <p class="admin-menu-category">Main</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/index.php" class="admin-menu-link active">
                        <i class="fas fa-tachometer-alt admin-menu-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="/admin/orders.php" class="admin-menu-link">
                        <i class="fas fa-shopping-cart admin-menu-icon"></i>
                        <span>Orders</span>
                    </a>
                </li>
            </ul>

            <p class="admin-menu-category">Catalog</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/products.php" class="admin-menu-link">
                        <i class="fas fa-box admin-menu-icon"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="admin-menu-item">
                    <a href="/admin/categories.php" class="admin-menu-link">
                        <i class="fas fa-tags admin-menu-icon"></i>
                        <span>Categories</span>
                    </a>
                </li>
            </ul>

            <p class="admin-menu-category">Users</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/customers.php" class="admin-menu-link">
                        <i class="fas fa-users admin-menu-icon"></i>
                        <span>Customers</span>
                    </a>
                </li>
            </ul>

            <p class="admin-menu-category">Settings</p>
            <ul class="admin-menu-list">
                <li class="admin-menu-item">
                    <a href="/admin/settings.php" class="admin-menu-link">
                        <i class="fas fa-cog admin-menu-icon"></i>
                        <span>General Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

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

        <h1>Dashboard</h1>

        <!-- Stats Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo formatPrice($total_revenue ?? 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_customers; ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon products">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="dashboard-charts">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Sales Overview</h3>
                    <div class="chart-period">Last 30 days</div>
                </div>
                <div class="chart-container">
                    <canvas id="sales-chart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Top Categories</h3>
                </div>
                <div class="chart-container">
                    <canvas id="products-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <h3 class="data-table-title">Recent Orders</h3>
                <a href="/admin/orders.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_orders) > 0): ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td><?php echo formatPrice($order['total']); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin/order-details.php?id=<?php echo $order['id']; ?>"
                                    class="action-button view" title="View Order">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No orders found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Low Stock Products -->
        <div class="data-table-wrapper">
            <div class="data-table-header">
                <h3 class="data-table-title">Low Stock Products</h3>
                <a href="/admin/products.php" class="btn btn-outline btn-sm">View All Products</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($low_stock_products) > 0): ?>
                    <?php foreach ($low_stock_products as $product): ?>
                    <tr>
                        <td>
                            <div class="product-info-cell">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>" width="40" height="40">
                                <span><?php echo htmlspecialchars($product['name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                        <td><?php echo formatPrice($product['price']); ?></td>
                        <td>
                            <span
                                class="stock-count <?php echo $product['stock_quantity'] <= 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                <?php echo $product['stock_quantity']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin/edit-product.php?id=<?php echo $product['id']; ?>"
                                    class="action-button edit" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No low stock products found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleBtn = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('admin-sidebar');
    const content = document.getElementById('admin-content');

    if (toggleBtn && sidebar && content) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Sample data for charts (would be replaced with real data from backend)
        const salesData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Sales',
                data: [12, 19, 3, 5, 2, 3, 20, 33, 23, 12, 33, 10],
                borderColor: '#4a6cf7',
                backgroundColor: 'rgba(74, 108, 247, 0.1)',
                tension: 0.3,
                fill: true
            }]
        };

        const categoryData = {
            labels: ['Electronics', 'Clothing', 'Books', 'Home', 'Sports'],
            datasets: [{
                data: [30, 25, 15, 20, 10],
                backgroundColor: ['#4a6cf7', '#f7c948', '#f74a4a', '#4af78c', '#a64af7']
            }]
        };

        // Sales chart
        const salesChart = document.getElementById('sales-chart');
        if (salesChart) {
            new Chart(salesChart, {
                type: 'line',
                data: salesData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Categories chart
        const productsChart = document.getElementById('products-chart');
        if (productsChart) {
            new Chart(productsChart, {
                type: 'doughnut',
                data: categoryData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>