<?php
$page_title = "Manage Products";
$is_admin = true;
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    try {
        // Delete product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$product_id]);
        
        if ($result) {
            $success_message = "Product deleted successfully.";
        } else {
            $error_message = "Failed to delete product.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : [];
    
    if (!empty($selected_products)) {
        if ($action === 'delete') {
            try {
                // Prepare placeholders for IN clause
                $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';
                
                // Delete selected products
                $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                $result = $stmt->execute($selected_products);
                
                if ($result) {
                    $success_message = count($selected_products) . " products deleted successfully.";
                } else {
                    $error_message = "Failed to delete products.";
                }
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } elseif ($action === 'feature') {
            try {
                // Prepare placeholders for IN clause
                $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';
                
                // Update selected products
                $stmt = $conn->prepare("UPDATE products SET featured = 1 WHERE id IN ($placeholders)");
                $result = $stmt->execute($selected_products);
                
                if ($result) {
                    $success_message = count($selected_products) . " products marked as featured.";
                } else {
                    $error_message = "Failed to update products.";
                }
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } elseif ($action === 'unfeature') {
            try {
                // Prepare placeholders for IN clause
                $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';
                
                // Update selected products
                $stmt = $conn->prepare("UPDATE products SET featured = 0 WHERE id IN ($placeholders)");
                $result = $stmt->execute($selected_products);
                
                if ($result) {
                    $success_message = count($selected_products) . " products removed from featured.";
                } else {
                    $error_message = "Failed to update products.";
                }
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "No products selected.";
    }
}

// Get categories for filter
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Initialize query parameters
$params = [];
$where_clauses = [];

// Category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $_GET['category'];
}

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Stock filter
if (isset($_GET['stock']) && !empty($_GET['stock'])) {
    if ($_GET['stock'] === 'in_stock') {
        $where_clauses[] = "p.stock_quantity > 0";
    } elseif ($_GET['stock'] === 'out_of_stock') {
        $where_clauses[] = "p.stock_quantity <= 0";
    } elseif ($_GET['stock'] === 'low_stock') {
        $where_clauses[] = "p.stock_quantity > 0 AND p.stock_quantity <= 5";
    }
}

// Build the WHERE clause
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);
}

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = "";

switch ($sort) {
    case 'name_asc':
        $order_by = " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $order_by = " ORDER BY p.name DESC";
        break;
    case 'price_low':
        $order_by = " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $order_by = " ORDER BY p.price DESC";
        break;
    case 'stock_low':
        $order_by = " ORDER BY p.stock_quantity ASC";
        break;
    case 'stock_high':
        $order_by = " ORDER BY p.stock_quantity DESC";
        break;
    case 'oldest':
        $order_by = " ORDER BY p.created_at ASC";
        break;
    case 'newest':
    default:
        $order_by = " ORDER BY p.created_at DESC";
        break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total products count for pagination
$count_sql = "SELECT COUNT(*) FROM products p" . $where_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id" . 
        $where_sql . $order_by . 
        " LIMIT " . $per_page . " OFFSET " . $offset;
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

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

        <div class="admin-page-header">
            <h1>Products</h1>
            <a href="/admin/add-product.php" class="btn btn-primary">Add New Product</a>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters-bar">
            <form id="filter-form" action="/admin/products.php" method="GET" class="row">
                <div class="filter-item">
                    <label for="filter-category">Category</label>
                    <select id="filter-category" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                            <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="filter-stock">Stock Status</label>
                    <select id="filter-stock" name="stock" class="form-control">
                        <option value="">All</option>
                        <option value="in_stock"
                            <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'in_stock') ? 'selected' : ''; ?>>In
                            Stock</option>
                        <option value="out_of_stock"
                            <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'out_of_stock') ? 'selected' : ''; ?>>
                            Out of Stock</option>
                        <option value="low_stock"
                            <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'low_stock') ? 'selected' : ''; ?>>
                            Low Stock</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="filter-sort">Sort By</label>
                    <select id="filter-sort" name="sort" class="form-control">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)
                        </option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)
                        </option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price (Low to
                            High)</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price (High to
                            Low)</option>
                        <option value="stock_low" <?php echo $sort == 'stock_low' ? 'selected' : ''; ?>>Stock (Low to
                            High)</option>
                        <option value="stock_high" <?php echo $sort == 'stock_high' ? 'selected' : ''; ?>>Stock (High to
                            Low)</option>
                    </select>
                </div>

                <div class="filter-item">
                    <label for="filter-search">Search</label>
                    <div class="search-input">
                        <input type="text" id="filter-search" name="search" class="form-control"
                            placeholder="Search by name, SKU..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="/admin/products.php" class="btn btn-outline">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="data-table-wrapper">
            <form id="bulk-action-form" method="POST">
                <div class="data-table-header">
                    <div class="bulk-actions">
                        <select name="bulk_action" class="form-control">
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete</option>
                            <option value="feature">Mark as Featured</option>
                            <option value="unfeature">Remove from Featured</option>
                        </select>
                        <button type="submit" class="btn btn-outline">Apply</button>
                    </div>

                    <div class="data-table-info">
                        Showing <?php echo min(($page - 1) * $per_page + 1, $total_products); ?> to
                        <?php echo min($page * $per_page, $total_products); ?> of <?php echo $total_products; ?>
                        products
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>"
                                    class="product-checkbox">
                            </td>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50"
                                    style="object-fit: cover;">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td>
                                <span
                                    class="stock-count <?php echo $product['stock_quantity'] <= 0 ? 'out-of-stock' : ($product['stock_quantity'] <= 5 ? 'low-stock' : 'in-stock'); ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['featured']): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/admin/edit-product.php?id=<?php echo $product['id']; ?>"
                                        class="action-button edit" title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/product-details.php?id=<?php echo $product['id']; ?>"
                                        class="action-button view" title="View Product" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/admin/products.php?delete=<?php echo $product['id']; ?>"
                                        class="action-button delete delete-product" title="Delete Product">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No products found matching your criteria.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

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

    // Select all checkbox
    const selectAll = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update select all if any individual checkbox changes
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(productCheckboxes).every(c => c.checked);
                const someChecked = Array.from(productCheckboxes).some(c => c.checked);

                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            });
        });
    }

    // Confirm delete
    const deleteButtons = document.querySelectorAll('.delete-product');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(
                    'Are you sure you want to delete this product? This action cannot be undone.'
                )) {
                e.preventDefault();
            }
        });
    });

    // Confirm bulk actions
    const bulkActionForm = document.getElementById('bulk-action-form');

    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const action = document.querySelector('select[name="bulk_action"]').value;
            const selectedProducts = document.querySelectorAll(
                'input[name="selected_products[]"]:checked');

            if (action === '') {
                e.preventDefault();
                alert('Please select an action');
                return;
            }

            if (selectedProducts.length === 0) {
                e.preventDefault();
                alert('Please select at least one product');
                return;
            }

            if (action === 'delete' && !confirm(
                    'Are you sure you want to delete the selected products? This action cannot be undone.'
                )) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>